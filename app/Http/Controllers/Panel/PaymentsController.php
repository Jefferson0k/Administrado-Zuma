<?php

namespace App\Http\Controllers\Panel;

use App\Casts\MoneyCast;
use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Helpers\MoneyConverter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Investment;
use App\Models\Invoice;
use App\Models\Movement;
use App\Models\Payment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelType;
use Illuminate\Support\Str;

class PaymentsController extends Controller{
    public function comparacion(Request $request){
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('excel_file');
        $data = Excel::toArray([], $file, null, ExcelType::XLSX);
        $sheet = $data[0] ?? [];

        if (empty($sheet)) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'El archivo está vacío',
            ]);
        }

        $headers = array_map('strval', $sheet[0]);
        
        // Validar que existan las columnas requeridas
        $requiredColumns = ['document', 'RUC_client', 'invoice_number', 'loan_number', 
                           'estimated_pay_date', 'currency', 'amount', 'status', 'saldo'];
        
        $missingColumns = array_diff($requiredColumns, $headers);
        if (!empty($missingColumns)) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Faltan las siguientes columnas en el archivo: ' . implode(', ', $missingColumns),
            ]);
        }

        $jsonData = [];

        foreach (array_slice($sheet, 1) as $row) {
            $rowData = array_combine($headers, $row);

            // Campos desde Excel
            $documentExcel          = trim(strval($rowData['document'] ?? ''));
            $rucClientExcel         = trim(strval($rowData['RUC_client'] ?? ''));
            $invoiceNumberExcel     = trim(strval($rowData['invoice_number'] ?? ''));
            $loanNumberExcel        = trim(strval($rowData['loan_number'] ?? ''));
            $estimatedPayDateExcel  = strval($rowData['estimated_pay_date'] ?? '');
            $currencyExcel          = strtoupper(trim(strval($rowData['currency'] ?? '')));
            $amountExcel            = floatval($rowData['amount'] ?? 0);
            $statusExcel            = strtolower(trim(strval($rowData['status'] ?? '')));
            $saldoExcel             = floatval($rowData['saldo'] ?? 0);

            // Normalizar fecha Excel a formato Y-m-d
            $fechaExcelFormatted = null;
            if (!empty($estimatedPayDateExcel)) {
                $fechaParts = explode('/', $estimatedPayDateExcel);
                if (count($fechaParts) === 3) {
                    $fechaExcelFormatted = $fechaParts[2] . '-' .
                        str_pad($fechaParts[1], 2, '0', STR_PAD_LEFT) . '-' .
                        str_pad($fechaParts[0], 2, '0', STR_PAD_LEFT);
                }
            }

            // Buscar empresa
            $company = Company::where('document', $documentExcel)->first();
            $invoice = null;
            $invoiceId = null; // Initialize invoice ID variable
            
            if ($company) {
                $invoice = $company->invoices()
                    ->where('RUC_client', $rucClientExcel)
                    ->where('invoice_number', $invoiceNumberExcel)
                    ->where('loan_number', $loanNumberExcel)
                    ->first();
                
                if ($invoice) {
                    $invoiceId = $invoice->id; // Set the invoice ID
                }
            }

            $detalle = [];
            $estado = 'Coincide';

            if (!$company) {
                $estado = 'No coincide';
                $detalle[] = "Empresa no registrada (Documento: '{$documentExcel}')";
            } elseif (!$invoice) {
                $estado = 'No coincide';
                $detalle[] = "Factura no encontrada (RUC Cliente: '{$rucClientExcel}', Nro Factura: '{$invoiceNumberExcel}', Loan: '{$loanNumberExcel}')";
            } else {
                // Comparar document (Company)
                if ($company->document === $documentExcel) {
                    $detalle[] = "Documento empresa: OK";
                } else {
                    $detalle[] = "Documento empresa: Diferente (BD: {$company->document} vs Excel: {$documentExcel})";
                    $estado = 'No coincide';
                }

                // Comparar RUC_client (Invoice)
                if ($invoice->RUC_client === $rucClientExcel) {
                    $detalle[] = "RUC Cliente: OK";
                } else {
                    $detalle[] = "RUC Cliente: Diferente (BD: {$invoice->RUC_client} vs Excel: {$rucClientExcel})";
                    $estado = 'No coincide';
                }

                // Comparar invoice_number
                if ($invoice->invoice_number === $invoiceNumberExcel) {
                    $detalle[] = "Nro Factura: OK";
                } else {
                    $detalle[] = "Nro Factura: Diferente (BD: {$invoice->invoice_number} vs Excel: {$invoiceNumberExcel})";
                    $estado = 'No coincide';
                }

                // Comparar loan_number
                if ($invoice->loan_number === $loanNumberExcel) {
                    $detalle[] = "Loan Number: OK";
                } else {
                    $detalle[] = "Loan Number: Diferente (BD: {$invoice->loan_number} vs Excel: {$loanNumberExcel})";
                    $estado = 'No coincide';
                }

                // Comparar amount
                $amountInvoice = floatval($invoice->amount);
                if (abs($amountInvoice - $amountExcel) < 0.01) {
                    $detalle[] = 'Monto: OK';
                } else {
                    $detalle[] = "Monto: Diferente (BD: {$amountInvoice} vs Excel: {$amountExcel})";
                    $estado = 'No coincide';
                }

                // Comparar fecha estimada
                if ($fechaExcelFormatted && $invoice->estimated_pay_date === $fechaExcelFormatted) {
                    $detalle[] = 'Fecha estimada: OK';
                } else {
                    $detalle[] = "Fecha estimada: Diferente (BD: {$invoice->estimated_pay_date} vs Excel: {$fechaExcelFormatted})";
                    $estado = 'No coincide';
                }

                // Comparar currency
                $currencyInvoice = strtoupper($invoice->currency);
                if ($currencyInvoice === $currencyExcel) {
                    $detalle[] = 'Moneda: OK';
                } else {
                    $detalle[] = "Moneda: Diferente (BD: {$currencyInvoice} vs Excel: {$currencyExcel})";
                    $estado = 'No coincide';
                }

                // Comparar status
                $statusInvoice = strtolower($invoice->status);
                if ($statusInvoice === $statusExcel) {
                    $detalle[] = "Estado: OK";
                } else {
                    $detalle[] = "Estado: Diferente (BD: {$statusInvoice} vs Excel: {$statusExcel})";
                    $estado = 'No coincide';
                }

                $detalle[] = "DEBUG: ID Factura: {$invoice->id}";
            }

            // Determinar tipo de pago basado en saldo vs amount
            $tipoPago = 'Sin determinar';
            if ($saldoExcel > 0 && $amountExcel > 0) {
                if (abs($saldoExcel - $amountExcel) < 0.01) {
                    $tipoPago = 'Pago normal';
                    $detalle[] = "Tipo pago: Normal (Saldo: {$saldoExcel} = Amount: {$amountExcel})";
                } else {
                    $tipoPago = 'Pago parcial';
                    $detalle[] = "Tipo pago: Parcial (Saldo: {$saldoExcel} ≠ Amount: {$amountExcel})";
                }
            } elseif ($saldoExcel == 0 && $amountExcel > 0) {
                $tipoPago = 'Pago normal';
                $detalle[] = "Tipo pago: Normal (Saldo pagado completamente)";
            } else {
                $detalle[] = "Tipo pago: Sin determinar (Saldo: {$saldoExcel}, Amount: {$amountExcel})";
            }

            // Agregar los campos al resultado
            $rowData['saldo'] = $saldoExcel;
            $rowData['estado'] = $estado;
            $rowData['tipo_pago'] = $tipoPago;
            $rowData['id_pago'] = $invoiceId; // Use the correctly defined variable
            $rowData['detalle'] = implode(' | ', $detalle);

            $jsonData[] = $rowData;
        }

        return response()->json([
            'success' => true,
            'data' => $jsonData,
        ]);
    }
    public function store(Request $request, $invoiceId){
        $request->validate([
            "amount_to_be_paid" => "required|numeric",
            "pay_date" => "required|date",
            "pay_type" => "required|in:total,partial",
            "reprogramation_date" => "nullable|date|required_if:pay_type,partial",
            "reprogramation_rate" => "nullable|numeric|required_if:pay_type,partial",
            "evidencia" => "required|file|mimes:pdf,jpg,jpeg,png,gif,doc,docx|max:10240", // 10MB máximo
        ]);

        try {
            $invoice = Invoice::findOrFail($invoiceId);
            $company = $invoice->company()->first();
            $amountToBePaidMoney = MoneyConverter::fromDecimal(
                $request->amount_to_be_paid,
                $invoice->currency
            );

            DB::beginTransaction();

            $payment = new Payment();
            $payment->invoice_id = $invoice->id;
            $payment->pay_type = $request->pay_type;
            $payment->pay_date = $request->pay_date;
            $payment->amount_to_be_paid = MoneyConverter::getValue($amountToBePaidMoney);
            $payment->reprogramation_date = $request->reprogramation_date;
            $payment->reprogramation_rate = $request->reprogramation_rate;

            // Subir evidencia a S3
            if ($request->hasFile('evidencia')) {
                $disk = Storage::disk('s3');
                $evidenceFile = $request->file('evidencia');
                $filename = Str::uuid() . '.' . $evidenceFile->getClientOriginalExtension();
                $path = "pagos/evidencias/{$invoice->id}/{$filename}";
                
                try {
                    $disk->putFileAs("pagos/evidencias/{$invoice->id}", $evidenceFile, $filename);
                    
                    // Guardar información de la evidencia en el payment
                    $payment->evidencia = $filename;
                    $payment->evidencia_path = $path;
                    $payment->evidencia_original_name = $evidenceFile->getClientOriginalName();
                    $payment->evidencia_size = $evidenceFile->getSize();
                    $payment->evidencia_mime_type = $evidenceFile->getMimeType();
                    
                    Log::info('Evidencia de pago subida exitosamente a S3', [
                        'invoice_id' => $invoice->id,
                        'filename' => $filename,
                        'path' => $path,
                        'original_name' => $evidenceFile->getClientOriginalName(),
                        'size' => $evidenceFile->getSize()
                    ]);
                    
                } catch (Exception $e) {
                    Log::error('Error al subir evidencia de pago a S3', [
                        'error' => $e->getMessage(),
                        'invoice_id' => $invoice->id,
                        'fileName' => $filename,
                        'path' => $path,
                    ]);
                    
                    DB::rollBack();
                    return response()->json([
                        "error" => "No se pudo subir la evidencia de pago. Intente nuevamente."
                    ], 422);
                }
            }

            // Actualizar monto pagado
            $invoicePaidAmountMoney = MoneyConverter::fromDecimal($invoice->paid_amount, $invoice->currency);
            $invoice->paid_amount = $invoicePaidAmountMoney->add($amountToBePaidMoney);

            if ($request->pay_type == "partial") {
                $invoice->status = "reprogramed";

                [$error, $partialPayment] = $payment->createPartialPayments(
                    $invoice,
                    $invoice->convertToDecimalPercent($request->input("apportioment_percentage", 0)),
                    $invoice->convertToDecimalPercent($request->reprogramation_rate)
                );

                if ($error) {
                    DB::rollBack();
                    return response()->json(["error" => $error->getMessage()], 422);
                }

                foreach ($partialPayment["items"] as $item) {
                    $investment = $item["investment"];
                    $investor = $item["investor"];
                    $amountToPay = new MoneyCast($item["amountToPay"]);
                    $newExpectedReturn = new MoneyCast($item["newExpectedReturn"]);
                    $newInvestmentAmount = new MoneyCast($item["newInvestmentAmount"]);

                    $investment->status = "reprogramed";
                    $investment->save();

                    $movement = new Movement();
                    $movement->currency = $invoice->currency;
                    $movement->amount = $amountToPay->money;
                    $movement->type = MovementType::PAYMENT;
                    $movement->status = MovementStatus::VALID;
                    $movement->confirm_status = MovementStatus::VALID;
                    $movement->investor_id = $investor->id;
                    $movement->description = "Pago parcial - Factura #{$invoice->invoice_number}";
                    $movement->save();

                    $reprogramedInvestment = new Investment();
                    $reprogramedInvestment->currency = $invoice->currency;
                    $reprogramedInvestment->amount = $newInvestmentAmount->money;
                    $reprogramedInvestment->return = $newExpectedReturn->money;
                    $reprogramedInvestment->rate = $request->reprogramation_rate;
                    $reprogramedInvestment->due_date = $request->reprogramation_date;
                    $reprogramedInvestment->status = "active";
                    $reprogramedInvestment->investor_id = $investor->id;
                    $reprogramedInvestment->invoice_id = $invoice->id;
                    $reprogramedInvestment->previous_investment_id = $investment->id;
                    $reprogramedInvestment->original_investment_id = $investment->original_investment_id ?? $investment->id;
                    $reprogramedInvestment->movement_id = $movement->id;
                    $reprogramedInvestment->save();

                    $wallet = $investor->getBalance($invoice->currency);
                    $walletAmountMoney = MoneyConverter::fromDecimal($wallet->amount, $invoice->currency);
                    $walletInvestedAmountMoney = MoneyConverter::fromDecimal($wallet->invested_amount, $invoice->currency);
                    $wallet->amount = $walletAmountMoney->add($amountToPay->money);
                    $wallet->invested_amount = $walletInvestedAmountMoney->subtract($amountToPay->money);
                    $wallet->save();

                    $investor->sendInvestmentPartialEmailNotification($payment, $investment, $amountToPay->money);
                }
            } else {
                $invoice->status = "paid";

                [$_, $items] = $payment->createTotalPayments($invoice);

                foreach ($items["items"] as $item) {
                    $investor = $item["investor"];
                    $investment = $item["investment"];
                    $netExpectedReturn = $item["net_expected_return"];
                    $itfAmount = $item["itf_amount"];

                    $investor->sendInvestmentFullyPaidEmailNotification(
                        $payment,
                        $investment,
                        $netExpectedReturn,
                        $itfAmount
                    );
                }
            }

            $invoice->save();
            $payment->save();

            DB::commit();

            return response()->json([
                "message" => $request->pay_type === "partial" ? "Pago parcial creado exitosamente." : "Pago total creado exitosamente.",
                "payment" => $payment,
                "invoice" => $invoice,
                "evidencia_info" => [
                    "filename" => $payment->evidencia,
                    "original_name" => $payment->evidencia_original_name,
                    "size" => $payment->evidencia_size,
                    "uploaded" => true
                ]
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error en el procesamiento de pago', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'invoice_id' => $invoiceId,
                'request_data' => $request->except(['evidencia'])
            ]);
            throw new Exception($th->getMessage());
        }
    }
}