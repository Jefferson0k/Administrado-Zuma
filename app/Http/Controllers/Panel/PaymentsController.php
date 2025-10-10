<?php

namespace App\Http\Controllers\Panel;

use App\Casts\MoneyCast;
use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Helpers\MoneyConverter;
use App\Http\Controllers\Controller;
use App\Http\Resources\Factoring\Deposit\DepositResources;
use App\Http\Resources\Factoring\Investment\InvestmentResource;
use App\Jobs\SendInvestmentFullyPaidEmail;
use App\Jobs\SendInvestmentPartialEmail;
use App\Models\Balance;
use App\Models\Company;
use App\Models\Deposit;
use App\Models\Investment;
use App\Models\Invoice;
use App\Models\Movement;
use App\Models\Payment;
use App\Notifications\InvoiceAnnulledRefundNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelType;
use Illuminate\Support\Str;



class PaymentsController extends Controller
{
    public function comparacion(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain,application/octet-stream',
        ]);

        $file = $request->file('excel_file');
        Log::info('✅ Archivo recibido', ['name' => $file->getClientOriginalName()]);
        $data = Excel::toArray([], $file, null, \Maatwebsite\Excel\Excel::XLSX);
        Log::info('✅ Datos leídos', ['sheet_count' => count($data)]);

        $sheet = $data[0] ?? [];

        if (empty($sheet)) {
            Log::warning('⚠️ El archivo está vacío');
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'El archivo está vacío',
            ]);
        }

        $headers = array_map(function ($header) {
            return trim(strval($header));
        }, $sheet[0]);
        Log::info('✅ Encabezados detectados', ['headers' => $headers]);



        // Encabezados requeridos
        $requiredColumns = [
            'NRO PRESTAMO',
            'RUC PROVEEDOR',
            'NRO FACTURA',
            'RUC ACEPTANTE',
            'MONEDA',
            'MONTO DOCUMENTO',
            'FECHA PAGO',
            'MONTO PAGADO',
            'ESTADO',
            'SITUACION'
        ];

        $missingColumns = array_diff($requiredColumns, $headers);
        if (!empty($missingColumns)) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Faltan las siguientes columnas en el archivo: ' . implode(', ', $missingColumns),
            ]);
        }

        // Mapeo de situaciones (type) de inglés a español
        $typeMapping = [
            'normal' => 'normal',
            'annulled' => 'anulado',
            'reprogramed' => 'reprogramado'
        ];

        // Mapeo de estados
        $estadoMapping = [
            0 => 'Pago de intereses',
            1 => 'Pago parcial',
            2 => 'Paga toda la factura'
        ];

        // Mapeo de monedas
        $currencyMapping = [
            'S/' => 'PEN',
            'S' => 'PEN',
            'PEN' => 'PEN',
            'USD' => 'USD',
            '$' => 'USD'
        ];

        $jsonData = [];

        $rows = array_slice($sheet, 1);

        // Filter out empty rows
        $rows = array_filter($rows, function ($row) {
            // Keep row if at least one cell has non-empty value
            return count(array_filter($row, fn($cell) => trim((string)$cell) !== '')) > 0;
        });

        foreach ($rows as $row) {

            Log::info('📄 Procesando fila', ['row' => $row]);

            $rowData = array_combine($headers, $row);

            // Extraer datos del Excel
            $loanNumberExcel        = trim(strval($rowData['NRO PRESTAMO'] ?? ''));
            $rucProveedorExcel      = trim(strval($rowData['RUC PROVEEDOR'] ?? ''));
            $invoiceNumberExcel     = trim(strval($rowData['NRO FACTURA'] ?? ''));
            $rucAceptanteExcel      = trim(strval($rowData['RUC ACEPTANTE'] ?? ''));
            $currencyExcel          = trim(strval($rowData['MONEDA'] ?? ''));
            $montoDocumentoExcel    = floatval($rowData['MONTO DOCUMENTO'] ?? 0);
            $estimatedPayDateExcel  = strval($rowData['FECHA PAGO'] ?? '');
            $montoPagadoExcel       = floatval($rowData['MONTO PAGADO'] ?? 0);
            $estadoExcel            = intval($rowData['ESTADO'] ?? -1);
            $situacionExcel         = strtolower(trim(strval($rowData['SITUACION'] ?? '')));

            // Normalizar moneda del Excel
            $currencyExcelNormalized = $currencyMapping[$currencyExcel] ?? $currencyExcel;
            $currencyExcelNormalized = strtoupper($currencyExcelNormalized);

            // Formatear fecha
            $fechaExcelFormatted = null;
            if (!empty($estimatedPayDateExcel)) {
                $fechaParts = explode('/', $estimatedPayDateExcel);
                if (count($fechaParts) === 3) {
                    $fechaExcelFormatted = $fechaParts[2] . '-' .
                        str_pad($fechaParts[1], 2, '0', STR_PAD_LEFT) . '-' .
                        str_pad($fechaParts[0], 2, '0', STR_PAD_LEFT);
                }
            }

            // Buscar factura - ESTRATEGIA ESTRICTA
            $invoice = Invoice::where('ruc_proveedor', $rucProveedorExcel) //RUC ACEPTANTE
                ->where('loan_number', $loanNumberExcel) //NRO
                ->where('invoice_number', $invoiceNumberExcel)
                ->first();

            Log::info('🔎 Buscando factura', [
                'ruc' => $rucAceptanteExcel,
                'prestamo' => $loanNumberExcel,
                'factura' => $invoiceNumberExcel,
                'found' => $invoice ? true : false
            ]);


            $invoiceId = null;
            $detalle = [];
            $estado = 'Coincide';
            $criterioBusqueda = 'Exacto (RUC + Prestamo + Factura)';

            if (!$invoice) {
                // Si no encuentra con los 3 campos, buscar solo por RUC + Factura
                $invoice = Invoice::where('ruc_proveedor', $rucProveedorExcel)
                    ->where('invoice_number', $invoiceNumberExcel)
                    ->first();

                if ($invoice) {
                    $criterioBusqueda = 'RUC + Factura (Prestamo diferente)';
                    $estado = 'No coincide'; // Porque el prestamo no coincide
                }
            }

            // VERIFICAR SI LA FACTURA YA FUE PAGADA
            if ($invoice && $invoice->statusPago === 'paid') {
                $estado = 'Ya pagada';
                $detalle[] = "FACTURA YA PAGADA - No se requiere validación adicional";
                $detalle[] = "ID Factura: {$invoice->id}";
                $detalle[] = "Estado en BD: {$invoice->statusPago}";
            } elseif (!$invoice) {
                $estado = 'No coincide';
                $detalle[] = "Factura no encontrada (RUC: '{$rucAceptanteExcel}', Prestamo: '{$loanNumberExcel}', Factura: '{$invoiceNumberExcel}')";
            } else {
                $invoiceId = $invoice->id;
                $amountInvoiceDecimal = floatval($invoice->amount);

                $detalle[] = "Criterio busqueda: {$criterioBusqueda}";
                $detalle[] = "Loan BD: '{$invoice->loan_number}', Invoice BD: '{$invoice->invoice_number}'";
                $detalle[] = "Status Pago BD: {$invoice->statusPago}";

                // Si la factura está reprogramada, marcamos como estado especial
                if ($invoice->statusPago === 'reprogramed') {
                    $estado = 'Reprogramada';
                    $detalle[] = "FACTURA REPROGRAMADA - Validación limitada";
                }

                // Comparar RUC Aceptante (RUC_CLIENTE)
                if ($invoice?->company?->document === $rucAceptanteExcel) {
                    $detalle[] = "RUC Aceptante: OK";
                } else {
                    $detalle[] = "RUC Aceptante: Diferente (BD: {$invoice?->company?->document} vs Excel: {$rucAceptanteExcel})";
                    if ($estado !== 'Reprogramada') $estado = 'No coincide';
                }



                //OPCIONAL
                // Comparar RUC PROVEEDOR 
                if ($invoice?->ruc_proveedor === $rucProveedorExcel) {
                    $detalle[] = "RUC Proveedor: OK";
                } else {
                    $detalle[] = "RUC Proveedor: Diferente (BD: {$invoice?->ruc_proveedor} vs Excel: {$rucProveedorExcel})";
                    if ($estado !== 'Reprogramada') $estado = 'No coincide';
                }




                // Comparar loan_number - AHORA ES OBLIGATORIO
                if ($invoice->loan_number === $loanNumberExcel) {
                    $detalle[] = "Nro Prestamo: OK";
                } else {
                    $detalle[] = "Nro Prestamo: Diferente (BD: '{$invoice->loan_number}' vs Excel: '{$loanNumberExcel}')";
                    if ($estado !== 'Reprogramada') $estado = 'No coincide';
                }

                // Comparar invoice_number
                if ($invoice->invoice_number === $invoiceNumberExcel) {
                    $detalle[] = "Nro Factura: OK";
                } else {
                    $detalle[] = "Nro Factura: Diferente (BD: {$invoice->invoice_number} vs Excel: {$invoiceNumberExcel})";
                    if ($estado !== 'Reprogramada') $estado = 'No coincide';
                }

                // Solo validar montos y fechas si no está reprogramada
                if ($estado !== 'Reprogramada') {
                    // Comparar monto total de la factura
                    if (abs($amountInvoiceDecimal - $montoDocumentoExcel) < 0.01) {
                        $detalle[] = 'Monto documento: OK';
                    } else {
                        $detalle[] = "Monto documento: Diferente (BD: {$amountInvoiceDecimal} vs Excel: {$montoDocumentoExcel})";
                        $estado = 'No coincide';
                    }

                    // Comparar fecha estimada
                    if ($invoice->estimated_pay_date && $fechaExcelFormatted) {

                        $fechaBD = Carbon::parse($invoice->estimated_pay_date)->format('Y-m-d');
                        $fechaExcel = Carbon::parse($fechaExcelFormatted)->format('Y-m-d');
                        Log::info('🗓️ Comparando fechas', [
                            'fecha_bd' => $fechaBD,
                            'fecha_excel' => $fechaExcel
                        ]);

                        if ($fechaBD === $fechaExcel) {
                            $detalle[] = 'Fecha estimada: OK';
                        } else {
                            $detalle[] = "Fecha estimada: Diferente (BD: {$fechaBD} vs Excel: {$fechaExcel})";
                            $estado = 'No coincide';
                        }
                    } else {
                        $detalle[] = 'Fecha estimada: No se puede comparar (datos faltantes)';
                    }
                }

                // Comparar currency
                $currencyInvoice = strtoupper($invoice->currency);
                if ($currencyInvoice === $currencyExcelNormalized) {
                    $detalle[] = 'Moneda: OK';
                } else {
                    $detalle[] = "Moneda: Diferente (BD: {$currencyInvoice} vs Excel: {$currencyExcel} -> {$currencyExcelNormalized})";
                    if ($estado !== 'Reprogramada') $estado = 'No coincide';
                }

                // Comparar type (situacion)
                $typeInvoice = strtolower($invoice->type ?? '');
                $typeInvoiceEspanol = $typeMapping[$typeInvoice] ?? $typeInvoice;

                if ($typeInvoiceEspanol === $situacionExcel) {
                    $detalle[] = "Situación: OK";
                } else {
                    $detalle[] = "Situación: Diferente (BD: {$typeInvoiceEspanol} vs Excel: {$situacionExcel})";
                    if ($estado !== 'Reprogramada') $estado = 'No coincide';
                }

                $detalle[] = "ID Factura: {$invoice->id}";
            }

            // Determinar tipo de pago basado en ESTADO (solo si no está pagada)
            $tipoPago = 'Sin determinar';
            if ($estado !== 'Ya pagada') {
                if (array_key_exists($estadoExcel, $estadoMapping)) {
                    $tipoPago = $estadoMapping[$estadoExcel];
                    $detalle[] = "Tipo pago: {$tipoPago}";
                } else {
                    $detalle[] = "Tipo pago: Estado inválido ({$estadoExcel})";
                }
            } else {
                $detalle[] = "Tipo pago: No aplica (factura ya pagada)";
            }

            // Preparar datos de salida
            $rowData['monto_documento'] = $montoDocumentoExcel;
            $rowData['monto_pagado'] = $montoPagadoExcel;
            $rowData['estado'] = $estado;
            $rowData['tipo_pago'] = $tipoPago;
            $rowData['id_pago'] = $invoiceId;
            $rowData['detalle'] = implode(' | ', $detalle);

            $jsonData[] = $rowData;

            Log::info('✅ Resultado comparación', [
                'invoice_number' => $invoiceNumberExcel,
                'estado' => $estado,
                'detalle' => $detalle
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $jsonData,
        ]);
    }
    /*public function store(Request $request, $invoiceId){
        $request->validate([
            "amount_to_be_paid" => "required|numeric",
            "pay_date" => "required|date",
            "pay_type" => "required|in:total,partial",
            "reprogramation_date" => "nullable|date|required_if:pay_type,partial",
            "reprogramation_rate" => "nullable|numeric|required_if:pay_type,partial",
            "payment_attachments" => "required|array|min:1",
            "payment_attachments.*" => "file|mimes:pdf,jpg,jpeg,png,gif,doc,docx|max:10240",
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

            // Manejar múltiples archivos
            $attachmentsData = [];
            if ($request->hasFile('payment_attachments')) {
                $disk = Storage::disk('s3');
                $evidenceFiles = $request->file('payment_attachments');

                foreach ($evidenceFiles as $evidenceFile) {
                    $filename = Str::uuid() . '.' . $evidenceFile->getClientOriginalExtension();
                    $path = "pagos/evidencias/{$invoice->id}/{$filename}";
                    
                    try {
                        $disk->putFileAs("pagos/evidencias/{$invoice->id}", $evidenceFile, $filename);
                        
                        $attachmentsData[] = [
                            'filename' => $filename,
                            'path' => $path,
                            'original_name' => $evidenceFile->getClientOriginalName(),
                            'size' => $evidenceFile->getSize(),
                            'mime_type' => $evidenceFile->getMimeType(),
                            'uploaded_at' => now()->toDateTimeString()
                        ];
                        
                    } catch (Exception $e) {
                        DB::rollBack();
                        return response()->json([
                            "error" => "No se pudo subir la evidencia de pago. Intente nuevamente."
                        ], 422);
                    }
                }
                
                // Guardar información de archivos
                $payment->evidencia = json_encode(array_column($attachmentsData, 'filename'));
                $payment->evidencia_data = json_encode($attachmentsData);
                $payment->evidencia_count = count($attachmentsData);
                
                // Mantener compatibilidad con el campo original para el primer archivo
                if (!empty($attachmentsData)) {
                    $firstFile = $attachmentsData[0];
                    $payment->evidencia_path = $firstFile['path'];
                    $payment->evidencia_original_name = $firstFile['original_name'];
                    $payment->evidencia_size = $firstFile['size'];
                    $payment->evidencia_mime_type = $firstFile['mime_type'];
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

                foreach ($partialPayment["items"] as $index => $item) {
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

                    // REEMPLAZADO: Envío de email directo por Job
                    SendInvestmentPartialEmail::dispatch(
                        $investor, 
                        $payment, 
                        $investment, 
                        MoneyConverter::getValue($amountToPay->money)
                    )->delay(now()->addSeconds($index * 2)); // Espaciar emails cada 2 segundos
                }
            } else {
                $invoice->statusPago = "paid";

                [$_, $items] = $payment->createTotalPayments($invoice);

                foreach ($items["items"] as $index => $item) {
                    $investor = $item["investor"];
                    $investment = $item["investment"];
                    $netExpectedReturn = $item["net_expected_return"];
                    $itfAmount = $item["itf_amount"];

                    // REEMPLAZADO: Envío de email directo por Job
                    SendInvestmentFullyPaidEmail::dispatch(
                        $investor,
                        $payment,
                        $investment,
                        MoneyConverter::getValue($netExpectedReturn),
                        MoneyConverter::getValue($itfAmount)
                    )->delay(now()->addSeconds($index * 2)); // Espaciar emails cada 2 segundos
                }
            }

            $invoice->save();
            $payment->save();

            DB::commit();

            return response()->json([
                "message" => $request->pay_type === "partial" ? "Pago parcial creado exitosamente." : "Pago total creado exitosamente.",
                "payment" => $payment,
                "invoice" => $invoice,
                "attachments_info" => $attachmentsData,
                "email_status" => "Los emails de notificación se están enviando en segundo plano"
            ]);
            
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error en el procesamiento de pago', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'invoice_id' => $invoiceId,
                'request_data' => $request->except(['payment_attachments'])
            ]);
            
            return response()->json([
                "error" => "Error interno del servidor al procesar el pago",
                "details" => config('app.debug') ? $th->getMessage() : null
            ], 500);
        }
    }*/

    public function store(Request $request, $invoiceId)
    {
        $request->validate([
            "amount_to_be_paid" => "required|numeric",
            "pay_date" => "required|date",
            "pay_type" => "required|in:total,partial",
            "reprogramation_date" => "nullable|date|required_if:pay_type,partial",
            "reprogramation_rate" => "nullable|numeric|required_if:pay_type,partial",
            "payment_attachments" => "required|array|min:1",
            "payment_attachments.*" => "file|mimes:pdf,jpg,jpeg,png,gif,doc,docx|max:10240",
        ]);

        DB::beginTransaction();
        try {
            $invoice = Invoice::findOrFail($invoiceId);

            $amountToBePaidMoney = MoneyConverter::fromDecimal(
                $request->amount_to_be_paid,
                $invoice->currency
            );

            $payment = new Payment();
            $payment->invoice_id = $invoice->id;
            $payment->pay_type = $request->pay_type;
            $payment->pay_date = $request->pay_date;
            $payment->amount_to_be_paid = MoneyConverter::getValue($amountToBePaidMoney);
            $payment->reprogramation_date = $request->reprogramation_date;
            $payment->reprogramation_rate = $request->reprogramation_rate;

            // Manejar múltiples archivos
            $attachmentsData = [];
            if ($request->hasFile('payment_attachments')) {
                $disk = Storage::disk('s3');
                $evidenceFiles = $request->file('payment_attachments');

                foreach ($evidenceFiles as $evidenceFile) {
                    $filename = Str::uuid() . '.' . $evidenceFile->getClientOriginalExtension();
                    $path = "pagos/evidencias/{$invoice->id}/{$filename}";

                    try {
                        $disk->putFileAs("pagos/evidencias/{$invoice->id}", $evidenceFile, $filename);

                        $attachmentsData[] = [
                            'filename' => $filename,
                            'path' => $path,
                            'original_name' => $evidenceFile->getClientOriginalName(),
                            'size' => $evidenceFile->getSize(),
                            'mime_type' => $evidenceFile->getMimeType(),
                            'uploaded_at' => now()->toDateTimeString()
                        ];
                    } catch (Exception $e) {
                        DB::rollBack();
                        return response()->json([
                            "error" => "No se pudo subir la evidencia de pago. Intente nuevamente."
                        ], 422);
                    }
                }

                // Guardar información de archivos
                $payment->evidencia = json_encode(array_column($attachmentsData, 'filename'));
                $payment->evidencia_data = json_encode($attachmentsData);
                $payment->evidencia_count = count($attachmentsData);

                // Mantener compatibilidad con el campo original para el primer archivo
                if (!empty($attachmentsData)) {
                    $firstFile = $attachmentsData[0];
                    $payment->evidencia_path = $firstFile['path'];
                    $payment->evidencia_original_name = $firstFile['original_name'];
                    $payment->evidencia_size = $firstFile['size'];
                    $payment->evidencia_mime_type = $firstFile['mime_type'];
                }
            }

            // Nivel 1 aprobado automáticamente al guardar evidencia
            $payment->approval1_status = 'approved';
            $payment->approval1_by = Auth::id();
            $payment->approval1_at = now();
            $payment->approval1_comment = "Evidencia registrada";

            $payment->save();

            DB::commit();

            return response()->json([
                "message" => "Pago registrado con éxito. Pendiente de aprobación final (nivel 2).",
                "payment" => $payment,
                "attachments_info" => $attachmentsData
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error en registro de pago', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'invoice_id' => $invoiceId,
                'request_data' => $request->except(['payment_attachments'])
            ]);

            return response()->json([
                "error" => "Error interno del servidor al registrar el pago",
                "details" => config('app.debug') ? $th->getMessage() : null
            ], 500);
        }
    }

    /**
     * Paso 2: Aprobación final del pago (Aprobación Nivel 2)
     */
    public function approveLevel2($paymentId)
    {
        DB::beginTransaction();
        try {
            $payment = Payment::findOrFail($paymentId);
            $invoice = $payment->invoice;

            if ($payment->approval1_status !== 'approved') {
                return response()->json(["error" => "No puede aprobarse sin aprobación nivel 1"], 422);
            }

            $amountToBePaidMoney = MoneyConverter::fromDecimal(
                $payment->amount_to_be_paid,
                $invoice->currency
            );

            // Sumar al monto pagado
            $invoicePaidAmountMoney = MoneyConverter::fromDecimal($invoice->paid_amount, $invoice->currency);
            $invoice->paid_amount = $invoicePaidAmountMoney->add($amountToBePaidMoney);

            if ($payment->pay_type == "partial") {
                $invoice->status = "reprogramed";
                [$error, $partialPayment] = $payment->createPartialPayments(
                    $invoice,
                    $invoice->convertToDecimalPercent(request("apportioment_percentage", 0)),
                    $invoice->convertToDecimalPercent($payment->reprogramation_rate)
                );

                if ($error) {
                    DB::rollBack();
                    return response()->json(["error" => $error->getMessage()], 422);
                }

                foreach ($partialPayment["items"] as $index => $item) {
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
                    $reprogramedInvestment->rate = $payment->reprogramation_rate;
                    $reprogramedInvestment->due_date = $payment->reprogramation_date;
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

                    SendInvestmentPartialEmail::dispatch(
                        $investor,
                        $payment,
                        $investment,
                        MoneyConverter::getValue($amountToPay->money)
                    )->delay(now()->addSeconds($index * 2));
                }
            } else {
                $invoice->statusPago = "paid";

                [$_, $items] = $payment->createTotalPayments($invoice);

                foreach ($items["items"] as $index => $item) {
                    $investor = $item["investor"];
                    $investment = $item["investment"];
                    $netExpectedReturn = $item["net_expected_return"];
                    $itfAmount = $item["itf_amount"];

                    SendInvestmentFullyPaidEmail::dispatch(
                        $investor,
                        $payment,
                        $investment,
                        MoneyConverter::getValue($netExpectedReturn),
                        MoneyConverter::getValue($itfAmount)
                    )->delay(now()->addSeconds($index * 2));
                }
            }

            $invoice->save();

            // Nivel 2 aprobado
            $payment->approval2_status = 'approved';
            $payment->approval2_by = Auth::id();
            $payment->approval2_at = now();
            $payment->approval2_comment = "Pago aplicado y aprobado nivel 2";
            $payment->save();

            DB::commit();

            return response()->json([
                "message" => $payment->pay_type === "partial" ? "Pago parcial aprobado y aplicado exitosamente." : "Pago total aprobado y aplicado exitosamente.",
                "payment" => $payment,
                "invoice" => $invoice,
                "email_status" => "Los emails de notificación se están enviando en segundo plano"
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error en aprobación final del pago', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'payment_id' => $paymentId
            ]);

            return response()->json([
                "error" => "Error interno del servidor al aprobar el pago",
                "details" => config('app.debug') ? $th->getMessage() : null
            ], 500);
        }
    }

    public function storeReembloso(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'pay_type' => 'required|in:reembloso',
            'pay_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'comment' => 'nullable|string',
            'nro_operation' => 'required|string',
            'currency' => 'required|string|size:3',
            'investor_id' => 'required|exists:investors,id',
            'resource_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ]);

        $invoice = Invoice::findOrFail($request->invoice_id);

        // Obtener la inversión original para este inversionista y factura
        $investment = Investment::where('invoice_id', $invoice->id)
            ->where('investor_id', $request->investor_id)
            ->firstOrFail();

        DB::beginTransaction();

        try {
            // Crear el Payment
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'pay_type' => $request->pay_type,
                'amount_to_be_paid' => $request->amount,
                'pay_date' => $request->pay_date,
                'approval1_status' => 'approved',
                'approval1_by' => Auth::id(),
                'approval1_comment' => $request->comment,
                'approval1_at' => now(),
                'approval2_status' => 'pending',
            ]);

            // Manejo del archivo
            $path = null;
            if ($request->hasFile('resource_path')) {
                $path = $request->file('resource_path')->store('refunds', 'public');
            }

            // Crear el Movement para el reembolso
            $movement = Movement::create([
                'currency' => $request->currency,
                'amount' => $request->amount,
                'type' => 'withdraw', // Reembolso
                'status' => MovementStatus::PENDING->value,
                'confirm_status' => MovementStatus::PENDING->value,
                'description' => "Reembolso de factura {$invoice->codigo} - Operación: {$request->nro_operation}",
                'origin' => 'inversionista',
                'investor_id' => $request->investor_id,
                'aprobacion_1' => now(),
                'aprobado_por_1' => Auth::user()->name ?? 'Sistema',
            ]);

            // Vincular el movimiento de reembolso con el movimiento original de la inversión
            $movement->update([
                'related_movement_id' => $investment->movement_id
            ]);

            // Crear el Deposit vinculado al movimiento de reembolso
            $deposit = Deposit::create([
                'nro_operation' => $request->nro_operation,
                'currency' => $request->currency,
                'amount' => $request->amount,
                'resource_path' => $path,
                'description' => "Solicitud de reembolso factura {$invoice->codigo}",
                'investor_id' => $request->investor_id,
                'bank_account_id' => $request->bank_account_id,
                'movement_id' => $movement->id,
                'payment_source' => 'reembloso',
                'type' => 'reembloso',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Actualizar el estado de la inversión y asignar el movimiento de reembolso
            $investment->update([
                'status' => 'pending', // o 'pending', según tu lógica
                'movement_reembloso' => $movement->id,
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Reembolso registrado, pendiente de confirmación.',
                'payment' => $payment,
                'deposit' => $deposit,
                'movement' => $movement,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function approvePayment(Request $request, $id)
    {
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
                    'aprobacion_1' => $payment->approval1_at,
                    'aprobado_por_1' => $payment->approval1_by,
                    'aprobacion_2' => $payment->approval2_at,
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
    public function show($id)
    {
        $investment = Investment::with([
            'movement.deposit',
            'investor',
            'property',
            'invoice'
        ])->findOrFail($id);

        return new InvestmentResource($investment);
    }
    public function anular(Request $request, $id)
    {
        $request->validate([
            'comment' => 'nullable|string|max:500',
        ]);

        $invoice = Invoice::findOrFail($id);
        $ok = $invoice->anularFactura(Auth::id(), $request->comment);

        if (!$ok) {
            return response()->json([
                'error' => 'La factura no puede ser anulada (ya pagada o ya anulada).'
            ], 422);
        }

        $invoice->status = 'rejected';
        $invoice->approval1_status = 'rejected';
        $invoice->approval2_status = 'rejected';
        $invoice->save();

        $investments = Investment::where('invoice_id', $invoice->id)->get();

        foreach ($investments as $investment) {
            $investor = $investment->investor;

            $balance = Balance::where('investor_id', $investor->id)
                ->where('currency', $investment->currency)
                ->first();

            if ($balance) {
                $balance->subtractInvestedAmount(MoneyConverter::fromDecimal($investment->amount, $investment->currency));
                $balance->subtractExpectedAmount(MoneyConverter::fromDecimal($investment->return, $investment->currency));
                $balance->addAmount(MoneyConverter::fromDecimal($investment->amount, $investment->currency));
                $balance->save();
            }

            $movement = new Movement();
            $movement->currency = $investment->currency;
            $movement->amount = $investment->amount;
            $movement->type = 'refund';
            $movement->status = MovementStatus::VALID;
            $movement->confirm_status = MovementStatus::VALID;
            $movement->description = 'Reembolso por anulación de factura #' . $invoice->id;
            $movement->investor_id = $investment->investor_id;
            $movement->save();

            $investment->status = 'reembolso'; // Corregí el typo 'reembloso'
            $investment->movement_reembloso = $movement->id;
            $investment->save();

            // 🔔 ENVIAR NOTIFICACIÓN AL INVERSIONISTA
            $investor->notify(new InvoiceAnnulledRefundNotification(
                invoice: $invoice,
                investment: $investment,
                comment: $request->comment
            ));
        }

        return response()->json([
            'message' => 'Factura e inversiones anuladas correctamente. Notificaciones enviadas a ' . $investments->count() . ' inversionista(s).',
            'invoice' => $invoice
        ]);
    }
}
