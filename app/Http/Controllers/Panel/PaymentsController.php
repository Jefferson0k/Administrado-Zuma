<?php

namespace App\Http\Controllers\Panel;

use App\Casts\MoneyCast;
use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Helpers\MoneyConverter;
use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\Company;
use App\Models\Deposit;
use App\Models\Investment;
use App\Models\Invoice;
use App\Models\Movement;
use App\Models\Payment;
use App\Notifications\InvestmentRefundNotification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
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
            "evidencia" => "required|file|mimes:pdf,jpg,jpeg,png,gif,doc,docx|max:10240",
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
            if ($request->hasFile('evidencia')) {
                $disk = Storage::disk('s3');
                $evidenceFile = $request->file('evidencia');
                $filename = Str::uuid() . '.' . $evidenceFile->getClientOriginalExtension();
                $path = "pagos/evidencias/{$invoice->id}/{$filename}";
                try {
                    $disk->putFileAs("pagos/evidencias/{$invoice->id}", $evidenceFile, $filename);
                    $payment->evidencia = $filename;
                    $payment->evidencia_path = $path;
                    $payment->evidencia_original_name = $evidenceFile->getClientOriginalName();
                    $payment->evidencia_size = $evidenceFile->getSize();
                    $payment->evidencia_mime_type = $evidenceFile->getMimeType();
                } catch (Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        "error" => "No se pudo subir la evidencia de pago. Intente nuevamente."
                    ], 422);
                }
            }
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
    /*public function storeReembloso(Request $request){
        $request->validate([
            'invoice_id'   => 'required|exists:invoices,id',
            'pay_type'     => 'required|in:total,partial,reembloso',
            'pay_date'     => 'required|date',
            'investments'  => 'required|array|min:1',
            'investments.*.investor_id'      => 'required_without:investments.*.investment_id|exists:investors,id',
            'investments.*.investment_id'    => 'required_without:investments.*.investor_id|exists:investments,id',
            'investments.*.amount'           => 'required|numeric|min:0.01',
            'investments.*.operation_number' => 'required|string',
            'investments.*.receipt'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'investments.*.comment'          => 'nullable|string',
        ]);
        DB::beginTransaction();
        try {
            $invoice = Invoice::findOrFail($request->invoice_id);
            $payment = Payment::create([
                'invoice_id'        => $invoice->id,
                'pay_type'          => $request->pay_type,
                'amount_to_be_paid' => collect($request->investments)->sum('amount'),
                'pay_date'          => $request->pay_date,
            ]);
            foreach ($request->investments as $inv) {
                $receiptPath = null;
                $investorId = $inv['investor_id'] ?? null;
                if (isset($inv['receipt']) && $inv['receipt'] instanceof UploadedFile) {
                    $disk = Storage::disk('s3');
                    $filename = uniqid("receipt_") . '.' . $inv['receipt']->getClientOriginalExtension();
                    $path = "payments/receipts/{$invoice->id}/{$filename}";
                    $disk->put($path, file_get_contents($inv['receipt']), 'public');
                    $receiptPath = $path;
                }
                if (isset($inv['investment_id'])) {
                    $existingInvestment = Investment::findOrFail($inv['investment_id']);
                    $investorId = $existingInvestment->investor_id;
                    if ($request->pay_type === 'reembloso') {
                        $currentReturn = MoneyConverter::fromDecimal($existingInvestment->return, $existingInvestment->currency);
                        $existingInvestment->update([
                            'status'           => 'inactive',
                            'return'           => 0,
                            'operation_number' => $inv['operation_number'],
                            'receipt_path'     => $receiptPath ?: $existingInvestment->receipt_path,
                            'comment'          => $inv['comment'] ?? $existingInvestment->comment,
                        ]);
                    }
                    $investment = $existingInvestment;
                } else {
                    $investment = Investment::create([
                        'currency'         => $invoice->currency,
                        'amount'           => $inv['amount'],
                        'return'           => $inv['return'] ?? 0,
                        'rate'             => $inv['rate'] ?? 0,
                        'due_date'         => $invoice->due_date,
                        'investor_id'      => $investorId,
                        'invoice_id'       => $invoice->id,
                        'status'           => $request->pay_type === 'reembloso' ? 'inactive' : 'paid',
                        'operation_number' => $inv['operation_number'],
                        'receipt_path'     => $receiptPath,
                        'comment'          => $inv['comment'] ?? null,
                    ]);
                    $currentReturn = MoneyConverter::fromDecimal($inv['return'] ?? 0, $invoice->currency);
                }
                $movementType = $request->pay_type === 'reembloso'
                                ? MovementType::INVESTMENT_REFUND->value
                                : MovementType::INVESTMENT_PAYMENT->value;
                $movement = Movement::create([
                    'currency'       => $invoice->currency,
                    'amount'         => $inv['amount'],
                    'type'           => $movementType,
                    'status'         => MovementStatus::CONFIRMED->value,
                    'confirm_status' => MovementStatus::CONFIRMED->value,
                    'investor_id'    => $investorId,
                    'description'    => $request->pay_type === 'reembloso'
                                        ? "Reembolso de factura #{$invoice->invoice_number}"
                                        : "Pago de factura #{$invoice->invoice_number}",
                ]);
                $investment->update(['movement_id' => $movement->id]);
                $balance = Balance::firstOrCreate([
                    'investor_id' => $investorId,
                    'currency'    => $invoice->currency,
                ]);
                $moneyAmount = MoneyConverter::fromDecimal($inv['amount'], $invoice->currency);
                $moneyReturn = MoneyConverter::fromDecimal($inv['return'] ?? 0, $invoice->currency);
                if ($request->pay_type === 'reembloso') {
                    $balance->subtractInvestedAmount($moneyAmount)
                            ->subtractExpectedAmount($moneyReturn)
                            ->addAmount($moneyAmount);
                } else {
                    $balance->addAmount($moneyAmount)
                            ->addExpectedAmount($moneyReturn);
                }
                $balance->save();
                $investor = $investment->investor;
                if ($investor && $investor->email) {
                    Log::info("Enviando InvestmentRefundNotification a: {$investor->email}");
                    $investor->notify(new InvestmentRefundNotification($investment, $invoice, $payment));
                }
            }
            if ($request->pay_type === 'total') {
                $invoice->update(['status' => 'paid']);
            }
            DB::commit();
            return response()->json([
                'message' => $request->pay_type === 'reembloso'
                            ? 'Reembolso registrado correctamente y retorno cancelado'
                            : 'Pago registrado correctamente',
                'payment' => $payment,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error en storeReembloso', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            return response()->json([
                'error' => 'Error al registrar el pago/reembolso: ' . $e->getMessage()
            ], 500);
        }
    }
    // PaymentsController.php
    public function approvePayment(Request $request, $paymentId)
    {
        $request->validate([
            'approval2_status' => 'required|in:approved,rejected',
            'approval2_comment' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $payment = Payment::findOrFail($paymentId);
            
            // Verificar que el pago esté listo para aprobación
            if ($payment->approval1_status !== 'submitted' || $payment->approval2_status !== 'pending') {
                return response()->json([
                    'error' => 'Este pago no está disponible para aprobación'
                ], 400);
            }

            // Actualizar estado de aprobación
            $payment->update([
                'approval2_status'  => $request->approval2_status,
                'approval2_by'      => Auth::id(),
                'approval2_comment' => $request->approval2_comment,
                'approval2_at'      => now(),
            ]);

            if ($request->approval2_status === 'approved') {
                // ✅ Ejecutar lógica financiera SOLO si es aprobado
                $this->executePaymentLogic($payment);
                
                $message = $payment->pay_type === 'reembloso' 
                    ? 'Reembolso aprobado y procesado correctamente'
                    : 'Pago aprobado y procesado correctamente';
            } else {
                // ❌ Rechazado → eliminar archivos subidos
                $this->cleanupRejectedPaymentFiles($payment);
                $message = 'Reembolso rechazado. Archivos eliminados.';
            }

            DB::commit();

            return response()->json([
                'message' => $message,
                'payment' => $payment->fresh(),
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error en approvePayment', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId,
            ]);
            return response()->json([
                'error' => 'Error al procesar la aprobación: ' . $e->getMessage()
            ], 500);
        }
    }

    private function executePaymentLogic(Payment $payment)
    {
        $invoice = $payment->invoice;
        $investmentsData = json_decode($payment->investments_data, true);

        foreach ($investmentsData as $inv) {
            $investorId = $inv['investor_id'];
            
            // Mover recibos de carpeta temporal a definitiva
            $finalReceiptPath = null;
            if ($inv['receipt_path']) {
                $finalReceiptPath = str_replace('/pending/', '/approved/', $inv['receipt_path']);
                Storage::disk('s3')->move($inv['receipt_path'], $finalReceiptPath);
            }

            // Procesar inversión existente o crear nueva
            if (isset($inv['investment_id'])) {
                $existingInvestment = Investment::findOrFail($inv['investment_id']);
                
                if ($payment->pay_type === 'reembloso') {
                    $existingInvestment->update([
                        'status'           => 'inactive',
                        'return'           => 0,
                        'operation_number' => $inv['operation_number'],
                        'receipt_path'     => $finalReceiptPath ?: $existingInvestment->receipt_path,
                        'comment'          => $inv['comment'] ?? $existingInvestment->comment,
                    ]);
                }
                $investment = $existingInvestment;
            } else {
                $investment = Investment::create([
                    'currency'         => $invoice->currency,
                    'amount'           => $inv['amount'],
                    'return'           => $inv['return'],
                    'rate'             => $inv['rate'],
                    'due_date'         => $invoice->due_date,
                    'investor_id'      => $investorId,
                    'invoice_id'       => $invoice->id,
                    'status'           => $payment->pay_type === 'reembloso' ? 'inactive' : 'paid',
                    'operation_number' => $inv['operation_number'],
                    'receipt_path'     => $finalReceiptPath,
                    'comment'          => $inv['comment'],
                ]);
            }

            // Crear movimiento financiero
            $movementType = $payment->pay_type === 'reembloso'
                            ? MovementType::INVESTMENT_REFUND->value
                            : MovementType::INVESTMENT_PAYMENT->value;
            
            $movement = Movement::create([
                'currency'       => $invoice->currency,
                'amount'         => $inv['amount'],
                'type'           => $movementType,
                'status'         => MovementStatus::CONFIRMED->value,
                'confirm_status' => MovementStatus::CONFIRMED->value,
                'investor_id'    => $investorId,
                'description'    => $payment->pay_type === 'reembloso'
                                    ? "Reembolso de factura #{$invoice->invoice_number}"
                                    : "Pago de factura #{$invoice->invoice_number}",
            ]);

            $investment->update(['movement_id' => $movement->id]);

            // Actualizar balance del inversor
            $balance = Balance::firstOrCreate([
                'investor_id' => $investorId,
                'currency'    => $invoice->currency,
            ]);

            $moneyAmount = MoneyConverter::fromDecimal($inv['amount'], $invoice->currency);
            $moneyReturn = MoneyConverter::fromDecimal($inv['return'], $invoice->currency);

            if ($payment->pay_type === 'reembloso') {
                $balance->subtractInvestedAmount($moneyAmount)
                        ->subtractExpectedAmount($moneyReturn)
                        ->addAmount($moneyAmount);
            } else {
                $balance->addAmount($moneyAmount)
                        ->addExpectedAmount($moneyReturn);
            }
            $balance->save();

            // Notificar al inversor
            $investor = $investment->investor;
            if ($investor && $investor->email) {
                Log::info("Enviando notificación aprobada a: {$investor->email}");
                $investor->notify(new InvestmentRefundNotification($investment, $invoice, $payment));
            }
        }

        // Actualizar estado de factura si es pago total
        if ($payment->pay_type === 'total') {
            $invoice->update(['status' => 'paid']);
        }
    }

    private function cleanupRejectedPaymentFiles(Payment $payment)
    {
        $investmentsData = json_decode($payment->investments_data, true);
        
        foreach ($investmentsData as $inv) {
            if ($inv['receipt_path']) {
                Storage::disk('s3')->delete($inv['receipt_path']);
            }
        }
    }

    public function getPendingPayments()
    {
        $pendingPayments = Payment::with(['invoice', 'approval1By', 'approval2By'])
            ->where('approval2_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'invoice_number' => $payment->invoice->invoice_number,
                    'pay_type' => $payment->pay_type,
                    'amount_to_be_paid' => $payment->amount_to_be_paid,
                    'pay_date' => $payment->pay_date,
                    'created_by' => $payment->approval1By->name ?? 'N/A',
                    'created_at' => $payment->created_at,
                    'investments_data' => json_decode($payment->investments_data, true),
                ];
            });

        return response()->json($pendingPayments);
    }*/
    public function storeReembloso(Request $request){
        $request->validate([
            'invoice_id'    => 'required|exists:invoices,id',
            'pay_type'      => 'required|in:reembloso',
            'pay_date'      => 'required|date',
            'amount'        => 'required|numeric|min:1',
            'comment'       => 'nullable|string',
            'nro_operation' => 'required|string',
            'currency'      => 'required|string|size:3',
            'investor_id'   => 'required|exists:investors,id',
            'resource_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
        $invoice = Invoice::findOrFail($request->invoice_id);
        DB::beginTransaction();
        try {
            $payment = Payment::create([
                'invoice_id'        => $invoice->id,
                'pay_type'          => $request->pay_type,
                'amount_to_be_paid' => $request->amount,
                'pay_date'          => $request->pay_date,
                'approval1_status'  => 'approved',
                'approval1_by'      => Auth::id(),
                'approval1_comment' => $request->comment,
                'approval1_at'      => now(),
                'approval2_status'  => 'pending',
            ]);
            $path = null;
            if ($request->hasFile('resource_path')) {
                $path = $request->file('resource_path')->store('refunds', 'public');
            }
            $deposit = Deposit::create([
                'nro_operation'   => $request->nro_operation,
                'currency'        => $request->currency,
                'amount'          => $request->amount,
                'resource_path'   => $path,
                'description'     => "Solicitud de reembolso factura {$invoice->codigo}",
                'investor_id'     => $request->investor_id,
                'movement_id'     => null,
                'payment_source'  => 'reembloso',
                'type'            => 'reembloso',
                'created_by'      => Auth::id(),
                'updated_by'      => Auth::id(),
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Reembolso registrado, pendiente de confirmación.',
                'payment' => $payment,
                'deposit' => $deposit,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function approvePayment(Request $request, $id){
        $request->validate([
            'status'  => 'required|in:approved,rejected',
            'comment' => 'nullable|string',
        ]);
        $payment = Payment::with('invoice')->findOrFail($id);
        if ($payment->approval1_status !== 'approved') {
            return response()->json([
                'error' => 'El pago aún no ha sido registrado por el primer aprobador.'
            ], 400);
        }
        DB::beginTransaction();
        try {
            $payment->update([
                'approval2_status'  => $request->status,
                'approval2_by'      => Auth::id(),
                'approval2_comment' => $request->comment,
                'approval2_at'      => now(),
            ]);
            if ($request->status === 'approved') {
                $deposit = Deposit::where('description', "Solicitud de reembolso factura {$payment->invoice->codigo}")
                            ->whereNull('movement_id')
                            ->first();
                $movement = Movement::create([
                    'amount'      => $payment->amount_to_be_paid,
                    'type'        => 'withdraw',
                    'currency'    => $payment->invoice->currency,
                    'status'      => 'confirmed',
                    'confirm_status' => 'confirmed',
                    'description' => 'Reembolso aprobado para la factura ' . $payment->invoice->codigo,
                    'origin'      => 'zuma',
                    'aprobacion_1'=> $payment->approval1_at,
                    'aprobado_por_1' => $payment->approval1_by,
                    'aprobacion_2'=> $payment->approval2_at,
                    'aprobado_por_2' => $payment->approval2_by,
                ]);
                if ($deposit) {
                    $deposit->update([
                        'movement_id' => $movement->id,
                    ]);
                }
                $invoice = $payment->invoice;
                $invoice->paid_amount -= $payment->amount_to_be_paid;
                if ($invoice->paid_amount < 0) {
                    $invoice->paid_amount = 0;
                }
                $invoice->save();
            }
            DB::commit();
            return response()->json([
                'message' => $request->status === 'approved'
                    ? 'Reembolso confirmado: movimiento y depósito enlazados.'
                    : 'Reembolso rechazado correctamente.',
                'payment'  => $payment,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}