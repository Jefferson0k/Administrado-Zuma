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
use App\Models\PaymentDetail;
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
use Throwable;

class PaymentsController extends Controller{
    public function comparacion(Request $request){
        $request->validate([
            'excel_file' => 'required|file|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain,application/octet-stream',
        ]);
        $file = $request->file('excel_file');
        Log::info('✅ Archivo recibido', ['name' => $file->getClientOriginalName()]);
        
        // Cambiar la forma de leer el Excel para manejar formatos de fecha
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
        
        $typeMapping = [
            'normal' => 'normal',
            'annulled' => 'anulado',
            'reprogramed' => 'reprogramado'
        ];

        $estadoMapping = [
            0 => 'Pago de intereses',
            1 => 'Pago parcial',
            2 => 'Paga toda la factura'
        ];

        $currencyMapping = [
            'S/' => 'PEN',
            'S' => 'PEN',
            'PEN' => 'PEN',
            'USD' => 'USD',
            '$' => 'USD'
        ];

        $jsonData = [];

        $rows = array_slice($sheet, 1);

        $rows = array_filter($rows, function ($row) {
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
            $montoPagadoExcel       = floatval($rowData['MONTO PAGADO'] ?? 0);
            $estadoExcel            = intval($rowData['ESTADO'] ?? -1);
            $situacionExcel         = strtolower(trim(strval($rowData['SITUACION'] ?? '')));

            // Normalizar moneda del Excel
            $currencyExcelNormalized = $currencyMapping[$currencyExcel] ?? $currencyExcel;
            $currencyExcelNormalized = strtoupper($currencyExcelNormalized);

            $invoice = Invoice::where('ruc_proveedor', $rucProveedorExcel)
                ->where('loan_number', $loanNumberExcel)
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
            $puedeProcesar = false;
            $tipoPago = 'Sin determinar';

            if (!$invoice) {
                $invoice = Invoice::where('ruc_proveedor', $rucProveedorExcel)
                    ->where('invoice_number', $invoiceNumberExcel)
                    ->first();

                if ($invoice) {
                    $criterioBusqueda = 'RUC + Factura (Prestamo diferente)';
                    $estado = 'No coincide';
                }
            }

            // ============================================================
            // VALIDACIÓN DE ESTADO DE PAGO Y PROCESAMIENTO POR FASES
            // ============================================================
            if (!$invoice) {
                $estado = 'No coincide';
                $puedeProcesar = false;
                $detalle[] = "❌ Factura no encontrada (RUC: '{$rucAceptanteExcel}', Prestamo: '{$loanNumberExcel}', Factura: '{$invoiceNumberExcel}')";
            } else {
                $invoiceId = $invoice->id;
                $statusPagoActual = $invoice->statusPago;
                $amountInvoiceDecimal = floatval($invoice->amount);

                $detalle[] = "Criterio busqueda: {$criterioBusqueda}";
                $detalle[] = "Loan BD: '{$invoice->loan_number}', Invoice BD: '{$invoice->invoice_number}'";
                $detalle[] = "Status Pago BD: " . ($statusPagoActual ?: 'vacío');

                // Determinar tipo de pago del Excel
                if (array_key_exists($estadoExcel, $estadoMapping)) {
                    $tipoPago = $estadoMapping[$estadoExcel];
                } else {
                    $tipoPago = "Estado inválido ({$estadoExcel})";
                    $puedeProcesar = false;
                    $estado = 'Error';
                    $detalle[] = "❌ Estado de pago inválido en Excel: {$estadoExcel}";
                }

                // LÓGICA DE VALIDACIÓN POR FASES
                if ($estadoExcel >= 0 && $estadoExcel <= 2) {
                    // Caso 1: Factura ya pagada completamente
                    if ($statusPagoActual === 'paid') {
                        $puedeProcesar = false;
                        $estado = 'Ya pagada';
                        $detalle[] = "⛔ FACTURA YA PAGADA COMPLETAMENTE - No se puede procesar";
                        $detalle[] = "Tipo pago solicitado: {$tipoPago}";
                        $detalle[] = "ID Factura: {$invoice->id}";
                    }
                    // Caso 2: Factura con pago de intereses previo
                    elseif ($statusPagoActual === 'intereses') {
                        if ($estadoExcel === 0) {
                            // Intentando pagar intereses nuevamente
                            $puedeProcesar = false;
                            $estado = 'Duplicado';
                            $detalle[] = "⛔ INTERESES YA PAGADOS PREVIAMENTE";
                            $detalle[] = "No se puede volver a pagar intereses";
                            $detalle[] = "Tipo pago solicitado: {$tipoPago}";
                        } else {
                            // Estado 1 (parcial) o 2 (total) después de intereses es válido
                            $puedeProcesar = true;
                            $detalle[] = "✅ Pago válido después de intereses";
                            $detalle[] = "Tipo pago: {$tipoPago}";
                            $detalle[] = "Pago previo: Intereses";
                        }
                    }
                    // Caso 3: Factura reprogramada
                    elseif ($statusPagoActual === 'reprogramed') {
                        $estado = 'Reprogramada';
                        $puedeProcesar = true;
                        $detalle[] = "⚠️ FACTURA REPROGRAMADA - Validación limitada";
                        $detalle[] = "Tipo pago: {$tipoPago}";
                    }
                    // Caso 4: Primera vez (statusPago vacío o null)
                    else {
                        $puedeProcesar = true;
                        $detalle[] = "✅ Primer pago de la factura";
                        $detalle[] = "Tipo pago: {$tipoPago}";
                        
                        if ($estadoExcel === 0) {
                            $detalle[] = "Se registrará como: Pago de intereses";
                        } elseif ($estadoExcel === 1) {
                            $detalle[] = "Se registrará como: Pago parcial";
                        } elseif ($estadoExcel === 2) {
                            $detalle[] = "Se registrará como: Pago total";
                        }
                    }
                }

                // VALIDACIONES ADICIONALES (solo si puede procesar)
                if ($puedeProcesar && $estado !== 'Reprogramada') {
                    // Validar RUC Aceptante
                    if ($invoice?->company?->document === $rucAceptanteExcel) {
                        $detalle[] = "RUC Aceptante: OK";
                    } else {
                        $detalle[] = "RUC Aceptante: Diferente (BD: {$invoice?->company?->document} vs Excel: {$rucAceptanteExcel})";
                        $estado = 'No coincide';
                    }

                    // Validar RUC Proveedor
                    if ($invoice?->ruc_proveedor === $rucProveedorExcel) {
                        $detalle[] = "RUC Proveedor: OK";
                    } else {
                        $detalle[] = "RUC Proveedor: Diferente (BD: {$invoice?->ruc_proveedor} vs Excel: {$rucProveedorExcel})";
                        $estado = 'No coincide';
                    }

                    // Validar Nro Prestamo
                    if ($invoice->loan_number === $loanNumberExcel) {
                        $detalle[] = "Nro Prestamo: OK";
                    } else {
                        $detalle[] = "Nro Prestamo: Diferente (BD: '{$invoice->loan_number}' vs Excel: '{$loanNumberExcel}')";
                        $estado = 'No coincide';
                    }

                    // Validar Nro Factura
                    if ($invoice->invoice_number === $invoiceNumberExcel) {
                        $detalle[] = "Nro Factura: OK";
                    } else {
                        $detalle[] = "Nro Factura: Diferente (BD: {$invoice->invoice_number} vs Excel: {$invoiceNumberExcel})";
                        $estado = 'No coincide';
                    }

                    // Validar Monto Documento
                    if (abs($amountInvoiceDecimal - $montoDocumentoExcel) < 0.01) {
                        $detalle[] = 'Monto documento: OK';
                    } else {
                        $detalle[] = "Monto documento: Diferente (BD: {$amountInvoiceDecimal} vs Excel: {$montoDocumentoExcel})";
                        $estado = 'No coincide';
                    }

                    // Validar Moneda
                    $currencyInvoice = strtoupper($invoice->currency);
                    if ($currencyInvoice === $currencyExcelNormalized) {
                        $detalle[] = 'Moneda: OK';
                    } else {
                        $detalle[] = "Moneda: Diferente (BD: {$currencyInvoice} vs Excel: {$currencyExcel} -> {$currencyExcelNormalized})";
                        $estado = 'No coincide';
                    }

                    // Validar Situación
                    $typeInvoice = strtolower($invoice->type ?? '');
                    $typeInvoiceEspanol = $typeMapping[$typeInvoice] ?? $typeInvoice;

                    if ($typeInvoiceEspanol === $situacionExcel) {
                        $detalle[] = "Situación: OK";
                    } else {
                        $detalle[] = "Situación: Diferente (BD: {$typeInvoiceEspanol} vs Excel: {$situacionExcel})";
                        $estado = 'No coincide';
                    }

                    // Si alguna validación falló, no se puede procesar
                    if ($estado === 'No coincide') {
                        $puedeProcesar = false;
                    }
                } elseif ($puedeProcesar && $estado === 'Reprogramada') {
                    // Para facturas reprogramadas, solo validaciones básicas
                    $detalle[] = "Validaciones completas omitidas por estado de reprogramación";
                }

                $detalle[] = "ID Factura: {$invoice->id}";
            }

            // Agregar información adicional al resultado
            $rowData['monto_documento'] = $montoDocumentoExcel;
            $rowData['monto_pagado'] = $montoPagadoExcel;
            $rowData['estado'] = $estado;
            $rowData['tipo_pago'] = $tipoPago;
            $rowData['id_pago'] = $invoiceId;
            $rowData['puede_procesar'] = $puedeProcesar;
            $rowData['detalle'] = implode(' | ', $detalle);

            $jsonData[] = $rowData;

            Log::info('✅ Resultado comparación', [
                'invoice_number' => $invoiceNumberExcel,
                'estado' => $estado,
                'puede_procesar' => $puedeProcesar,
                'tipo_pago' => $tipoPago,
                'detalle' => $detalle
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $jsonData,
        ]);
    }
    public function store(Request $request, $invoiceId)
    {
        $validated = $request->validate([
            'pay_type' => 'required|string|in:intereses,parcial,total',
            'amount_to_be_paid' => 'required|numeric',
            'pay_date' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // 🔹 Convertir fecha
            $payDate = Carbon::createFromFormat('d/m/Y', $validated['pay_date'])->format('Y-m-d');

            // 🔹 Buscar factura e inversión asociada
            $invoice = Invoice::findOrFail($invoiceId);
            $investment = Investment::where('invoice_id', $invoiceId)->first();

            if (!$investment) {
                throw new Exception("No se encontró la inversión asociada a la factura.");
            }

            $investorId = $investment->investor_id;
            $currency = $investment->currency; // 🔹 Obtener moneda de la inversión

            // 🔹 Buscar balance del inversionista
            $balance = Balance::where('investor_id', $investorId)->first();

            if (!$balance) {
                throw new Exception("No se encontró el balance del inversionista.");
            }

            // 🔹 Crear pago
            $payment = Payment::create([
                'invoice_id' => $invoiceId,
                'pay_type' => $validated['pay_type'],
                'amount_to_be_paid' => (int)($validated['amount_to_be_paid'] * 100),
                'pay_date' => $payDate,
            ]);

            // Variables base
            $bruto = $validated['amount_to_be_paid'];
            $recaudacion = 0;
            $returnEfectivizado = 0;

            // 🔹 Crear movimiento principal del pago
            $mainMovement = null;

            // 🔸 Pago de INTERESES
            if ($validated['pay_type'] === 'intereses') {
                $retorno = $investment->return; // Ej: 2.65
                $recaudacion = $retorno * 0.05;
                $returnEfectivizado = $retorno - $recaudacion;

                // Movimiento principal - Pago de intereses
                $mainMovement = Movement::create([
                    'amount' => $returnEfectivizado,
                    'type' => 'fixed_rate_interest_payment',
                    'currency' => $currency, // 🔹 Usar moneda de la inversión
                    'status' => MovementStatus::CONFIRMED->value,
                    'confirm_status' => MovementStatus::CONFIRMED->value,
                    'description' => 'Pago de intereses - Factura #' . $invoiceId,
                    'origin' => 'inversionista',
                    'investor_id' => $investorId,
                ]);

                // Movimiento de recaudación (tax)
                if ($recaudacion > 0) {
                    Movement::create([
                        'amount' => $recaudacion,
                        'type' => 'tax',
                        'currency' => $currency, // 🔹 Usar moneda de la inversión
                        'status' => MovementStatus::CONFIRMED->value,
                        'confirm_status' => MovementStatus::CONFIRMED->value,
                        'description' => 'Recaudación por intereses - Factura #' . $invoiceId,
                        'origin' => 'inversionista',
                        'investor_id' => $investorId,
                        'related_movement_id' => $mainMovement->id,
                    ]);
                }

                $investment->update([
                    'recaudacion' => $investment->recaudacion + $recaudacion,
                    'return_efectivizado' => $investment->return_efectivizado + $returnEfectivizado,
                    'status' => 'intereses',
                ]);

                $balance->update([
                    'expected_amount' => $balance->expected_amount - $retorno,
                    'amount' => $balance->amount + $returnEfectivizado,
                ]);
            }

            // 🔸 Pago PARCIAL
            elseif ($validated['pay_type'] === 'parcial') {
                $monto = $bruto;

                // Movimiento de retorno de capital parcial
                $mainMovement = Movement::create([
                    'amount' => $monto,
                    'type' => 'fixed_rate_capital_return',
                    'currency' => $currency, // 🔹 Usar moneda de la inversión
                    'status' => MovementStatus::CONFIRMED->value,
                    'confirm_status' => MovementStatus::CONFIRMED->value,
                    'description' => 'Retorno de capital parcial - Factura #' . $invoiceId,
                    'origin' => 'inversionista',
                    'investor_id' => $investorId,
                ]);

                $balance->update([
                    'invested_amount' => $balance->invested_amount - $monto,
                    'amount' => $balance->amount + $monto,
                ]);

                $investment->update(['status' => 'parcial']);
            }

            // 🔸 Pago TOTAL
            elseif ($validated['pay_type'] === 'total') {
                $monto = $bruto;

                // Movimiento de retorno de capital total
                $mainMovement = Movement::create([
                    'amount' => $monto,
                    'type' => 'fixed_rate_capital_return',
                    'currency' => $currency, // 🔹 Usar moneda de la inversión
                    'status' => MovementStatus::CONFIRMED->value,
                    'confirm_status' => MovementStatus::CONFIRMED->value,
                    'description' => 'Retorno de capital total - Factura #' . $invoiceId,
                    'origin' => 'inversionista',
                    'investor_id' => $investorId,
                ]);

                $balance->update([
                    'invested_amount' => $balance->invested_amount - $monto,
                    'amount' => $balance->amount + $monto,
                ]);

                $investment->update(['status' => 'paid']);
            }

            // 🔹 Actualizar el payment con el movement_id si es necesario
            if ($mainMovement) {
                $payment->update(['movement_id' => $mainMovement->id]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Pago procesado correctamente.',
                'payment' => [
                    'id' => $payment->id,
                    'bruto' => $bruto,
                    'recaudacion' => $recaudacion,
                    'neto' => $returnEfectivizado ?: $bruto,
                ],
                'movement_id' => $mainMovement?->id,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar el pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error al procesar el pago',
                'details' => $e->getMessage(),
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
            'resource_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
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
