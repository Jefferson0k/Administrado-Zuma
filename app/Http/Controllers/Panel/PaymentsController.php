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
use App\Models\StateNotification;
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

class PaymentsController extends Controller
{




    public function comparacion(Request $request)
    {
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
        $request->validate([
            "amount_to_be_paid" => "required|numeric", // Monto total de la factura
            "amount_to_pay" => "required|numeric",     // Monto del Excel
            "pay_date" => "required|string",
            "pay_type" => "required|in:total,partial,intereses",
            "investor_payments" => "required|array",
            "investor_payments.*.investor_id" => "required|string",
            "investor_payments.*.capital" => "required|numeric",
            "investor_payments.*.retorno_bruto" => "required|numeric",
            "investor_payments.*.recaudacion" => "required|numeric",
            "investor_payments.*.retorno_neto" => "required|numeric",
            "investor_payments.*.total_a_pagar" => "required|numeric",
            "investor_payments.*.es_adelantado" => "nullable|boolean",
            "investor_payments.*.dias_invertidos" => "nullable|integer",
            "es_adelantado" => "nullable|boolean",
            "dias_adelantados" => "nullable|integer",
        ]);

        DB::beginTransaction();
        try {
            $payDate = Carbon::createFromFormat('d-m-Y', $request->pay_date)->format('Y-m-d');

            $invoice = Invoice::with('investments')->findOrFail($invoiceId);

            if ($invoice->investments->isEmpty()) {
                throw new Exception('No hay inversiones asociadas a esta factura');
            }

            // Crear el registro de pago
            $payment = new Payment();
            $payment->invoice_id = $invoice->id;
            $payment->pay_type = $request->pay_type;

            // ✅ GUARDAR AMBOS MONTOS
            $payment->amount_to_be_paid = (int) round($request->amount_to_be_paid * 100); // Monto total factura
            $payment->amount_to_pay = (int) round($request->amount_to_pay * 100);         // Monto del Excel

            $payment->pay_date = $payDate;
            $payment->approval1_status = 'approved';
            $payment->approval1_by = Auth::id();
            $payment->approval1_at = now();
            $payment->approval1_comment = 'Aprobado automáticamente por instancia 1';

            Log::info('💾 GUARDANDO PAYMENT', [
                'amount_to_be_paid' => $request->amount_to_be_paid . ' (total factura)',
                'amount_to_pay' => $request->amount_to_pay . ' (monto Excel)',
                'pay_type' => $request->pay_type
            ]);

            $investmentDetails = [];
            $movementsCreated = [];

            // Verificar si es pago adelantado
            $dueDate = Carbon::parse($invoice->due_date);
            $paymentDate = Carbon::parse($payDate);
            $isAdelantado = $request->es_adelantado ?? $paymentDate->lt($dueDate);

            if ($isAdelantado) {
                $invoice->fecha_pagoadelantado = $payDate;
                Log::info('🕐 PAGO ADELANTADO DETECTADO', [
                    'dias_adelantados' => $request->dias_adelantados,
                    'fecha_vencimiento' => $dueDate->format('d-m-Y'),
                    'fecha_pago' => $paymentDate->format('d-m-Y')
                ]);
            }

            // Procesar cada pago de inversionista usando los datos del frontend
            foreach ($request->investor_payments as $investorPayment) {
                $investment = $invoice->investments->firstWhere('investor_id', $investorPayment['investor_id']);

                if (!$investment) {
                    Log::warning('Inversión no encontrada', [
                        'investor_id' => $investorPayment['investor_id'],
                        'invoice_id' => $invoiceId
                    ]);
                    continue;
                }

                // Capturar estado antes del pago
                $capitalAntes = ((int)($investment->amount ?? 0)) / 100;
                $retornoBrutoAntes = (float)($investment->return ?? 0);
                $estadoAntes = $investment->status;

                // ACTUALIZAR LOS CAMPOS EN INVESTMENTS
                $investment->return_efectivizado = $investorPayment['retorno_neto'];
                $investment->recaudacion = $investorPayment['recaudacion'];

                // ACTUALIZAR STATUS DEL INVESTMENT según tipo de pago
                if ($request->pay_type === 'total') {
                    // Pago total: se paga capital + intereses completos
                    $investment->status = 'paid';
                } elseif ($request->pay_type === 'intereses') {
                    // Solo intereses: el capital sigue activo
                    $investment->status = 'intereses';
                } elseif ($request->pay_type === 'partial') {
                    // Pago parcial: mantiene activo
                    $investment->status = 'active';
                }

                $investment->save();

                // ====== CREAR MOVIMIENTOS SEGÚN TIPO DE PAGO ======
                $movimientosInversion = $this->crearMovimientosSegunTipo(
                    $investment,
                    $investorPayment['capital'],
                    $investorPayment['retorno_bruto'],
                    $investorPayment['recaudacion'],
                    $investorPayment['retorno_neto'],
                    $request->pay_type,
                    $isAdelantado,
                    $payment
                );

                if (!empty($movimientosInversion)) {
                    $movementsCreated = array_merge($movementsCreated, $movimientosInversion);
                }

                // Log antes de actualizar balance
                Log::info('🚀 ANTES DE LLAMAR actualizarBalanceDirecto', [
                    'investor_id' => $investment->investor_id,
                    'tipo_pago' => $request->pay_type,
                    'capital' => $investorPayment['capital'],
                    'retorno_bruto' => $investorPayment['retorno_bruto'],
                    'total_a_pagar' => $investorPayment['total_a_pagar']
                ]);

                // ACTUALIZAR BALANCE CON LOS VALORES EN SOLES DEL FRONTEND
                $this->actualizarBalanceSegunTipo(
                    $investment->investor_id,
                    $investment->currency,
                    $investorPayment['capital'],
                    $investorPayment['retorno_bruto'],
                    $investorPayment['total_a_pagar'],
                    $request->pay_type
                );

                // Guardar detalle para respuesta
                $investmentDetails[] = [
                    'investment_id' => $investment->id,
                    'investor_id' => $investment->investor_id,
                    'investor_name' => $investment->inversionista ?? 'Inversionista',
                    'capital_pagado' => $investorPayment['capital'],
                    'retorno_bruto' => $investorPayment['retorno_bruto'],
                    'recaudacion' => $investorPayment['recaudacion'],
                    'retorno_neto_pagado' => $investorPayment['retorno_neto'],
                    'total_pagado' => $investorPayment['total_a_pagar'],
                    'tipo_pago' => $request->pay_type,
                    'es_adelantado' => $isAdelantado,
                    'capital_antes' => $capitalAntes,
                    'capital_despues' => ((int)($investment->amount ?? 0)) / 100,
                    'estado_antes' => $estadoAntes,
                    'estado_despues' => $investment->status,
                    'movements_ids' => $movimientosInversion,
                    'timestamp' => now()->toISOString()
                ];
            }

            // Guardar el pago
            $payment->save();

            // ✅ Actualizar monto pagado de la factura usando el monto del Excel
            $invoice->paid_amount += (int) round($request->amount_to_pay * 100);

            // Actualizar estado de la factura (statusPago)
            $this->actualizarEstadoFacturaDirecto($invoice, $request->pay_type);

            $invoice->save();

            Log::info('✅ PAGO PROCESADO EXITOSAMENTE', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount_to_be_paid' => $request->amount_to_be_paid,
                'amount_to_pay' => $request->amount_to_pay,
                'paid_amount_actualizado' => $invoice->paid_amount / 100
            ]);

            DB::commit();

            return response()->json([
                "message" => "Pago procesado correctamente",
                "payment" => $payment,
                "distribucion" => $investmentDetails,
                "movements" => $movementsCreated,
                "invoice" => $invoice->fresh(['investments']),
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error al procesar pago', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'invoice_id' => $invoiceId,
                'request_data' => $request->all()
            ]);
            return response()->json([
                "error" => "Error al procesar el pago: " . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Crea movimientos según el tipo de pago
     * 
     * TIPOS DE PAGO Y SUS MOVIMIENTOS:
     * 
     * 1. PAGO PARCIAL (partial):
     *    - 1 movimiento: Devolución parcial de capital (NO hay intereses, NO hay recaudación)
     * 
     * 2. PAGO DE INTERESES (intereses):
     *    - 2 movimientos: 
     *      a) Pago de retorno esperado (ganancia)
     *      b) Descuento 5% (recaudación)
     *    - NO se devuelve capital
     * 
     * 3. PAGO TOTAL (total):
     *    - 2 movimientos:
     *      a) Devolución de capital completo
     *      b) Pago de retorno esperado (ganancia completa SIN recaudación)
     * 
     * 4. PAGO ADELANTADO (cualquier tipo pero antes del vencimiento):
     *    - 3 movimientos:
     *      a) Devolución de capital
     *      b) Pago de retorno recalculado (proporcional a días invertidos)
     *      c) Descuento 5% sobre el retorno recalculado
     */
    private function crearMovimientosSegunTipo($investment, $capital, $retornoBruto, $recaudacion, $retornoNeto, $payType, $isAdelantado, $payment)
    {
        $movimientosCreados = [];

        try {
            Log::info('📥 CREANDO MOVIMIENTOS - Valores recibidos (SOLES)', [
                'tipo_pago' => $payType,
                'es_adelantado' => $isAdelantado,
                'capital' => $capital,
                'retorno_bruto' => $retornoBruto,
                'recaudacion' => $recaudacion,
                'retorno_neto' => $retornoNeto,
                'investor_id' => $investment->investor_id
            ]);

            switch ($payType) {
                case 'partial':
                    // ===== PAGO PARCIAL: SOLO 1 MOVIMIENTO (Devolución de capital) =====
                    Log::info('💰 PAGO PARCIAL: Creando 1 movimiento (devolución de capital)');

                    if ($capital > 0) {
                        $movCapital = $this->crearMovimiento(
                            $investment,
                            $capital,
                            'fixed_rate_capital_return',
                            "Devolución parcial de capital - Inversión #{$investment->id}"
                        );
                        $movimientosCreados[] = $movCapital;
                    }
                    break;

                case 'intereses':
                    // ===== PAGO DE INTERESES: 2 MOVIMIENTOS (Retorno + Recaudación) =====
                    Log::info('📊 PAGO DE INTERESES: Creando 2 movimientos (retorno + recaudación)');

                    // Movimiento 1: Pago de retorno bruto
                    if ($retornoBruto > 0) {
                        $movRetorno = $this->crearMovimiento(
                            $investment,
                            $retornoBruto,
                            'fixed_rate_interest_payment',
                            "Pago de intereses - Inversión #{$investment->id}"
                        );
                        $movimientosCreados[] = $movRetorno;
                    }

                    // Movimiento 2: Descuento del 5%
                    if ($recaudacion > 0) {
                        $movRecaudacion = $this->crearMovimiento(
                            $investment,
                            $recaudacion,
                            'tax',
                            "Recaudación 5% sobre intereses - Inversión #{$investment->id}"
                        );
                        $movimientosCreados[] = $movRecaudacion;
                    }
                    break;

                case 'total':
                    if ($isAdelantado) {
                        // ===== PAGO TOTAL ADELANTADO: 3 MOVIMIENTOS =====
                        Log::info('⏰ PAGO TOTAL ADELANTADO: Creando 3 movimientos');

                        // Movimiento 1: Devolución de capital
                        if ($capital > 0) {
                            $movCapital = $this->crearMovimiento(
                                $investment,
                                $capital,
                                'fixed_rate_capital_return',
                                "Devolución de capital (pago adelantado) - Inversión #{$investment->id}"
                            );
                            $movimientosCreados[] = $movCapital;
                        }

                        // Movimiento 2: Retorno recalculado (proporcional a días invertidos)
                        if ($retornoBruto > 0) {
                            $movRetorno = $this->crearMovimiento(
                                $investment,
                                $retornoBruto,
                                'fixed_rate_interest_payment',
                                "Retorno recalculado por pago adelantado - Inversión #{$investment->id}"
                            );
                            $movimientosCreados[] = $movRetorno;
                        }

                        // Movimiento 3: Recaudación 5% sobre retorno recalculado
                        if ($recaudacion > 0) {
                            $movRecaudacion = $this->crearMovimiento(
                                $investment,
                                $recaudacion,
                                'tax',
                                "Recaudación 5% (pago adelantado) - Inversión #{$investment->id}"
                            );
                            $movimientosCreados[] = $movRecaudacion;
                        }
                    } else {
                        // ===== PAGO TOTAL NORMAL: 2 MOVIMIENTOS (Capital + Retorno SIN recaudación) =====
                        Log::info('✅ PAGO TOTAL: Creando 2 movimientos (capital + retorno completo)');

                        // Movimiento 1: Devolución de capital
                        if ($capital > 0) {
                            $movCapital = $this->crearMovimiento(
                                $investment,
                                $capital,
                                'fixed_rate_capital_return',
                                "Devolución de capital - Inversión #{$investment->id}"
                            );
                            $movimientosCreados[] = $movCapital;
                        }

                        // Movimiento 2: Retorno completo (SIN recaudación)
                        if ($retornoBruto > 0) {
                            $movRetorno = $this->crearMovimiento(
                                $investment,
                                $retornoBruto,
                                'fixed_rate_interest_payment',
                                "Pago de retorno completo - Inversión #{$investment->id}"
                            );
                            $movimientosCreados[] = $movRetorno;
                        }
                    }
                    break;
            }

            Log::info('🎉 RESUMEN DE MOVIMIENTOS CREADOS', [
                'investor_id' => $investment->investor_id,
                'tipo_pago' => $payType,
                'es_adelantado' => $isAdelantado,
                'total_movimientos' => count($movimientosCreados),
                'movement_ids' => $movimientosCreados
            ]);

            return $movimientosCreados;
        } catch (Exception $e) {
            Log::error('❌ Error al crear movimientos', [
                'investment_id' => $investment->id,
                'investor_id' => $investment->investor_id,
                'tipo_pago' => $payType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Método auxiliar para crear un movimiento
     */
    private function crearMovimiento($investment, $amount, $type, $description)
    {
        $movement = new Movement();
        $movement->id = \Illuminate\Support\Str::ulid();
        $movement->currency = $investment->currency;
        $movement->amount = $amount; // El modelo maneja la conversión a centavos
        $movement->type = $type;
        $movement->status = MovementStatus::CONFIRMED;
        $movement->confirm_status = MovementStatus::CONFIRMED;
        $movement->description = $description . " - Monto: " . $this->formatCurrency($amount, $investment->currency);
        $movement->origin = 'inversionista';
        $movement->investor_id = $investment->investor_id;
        $movement->approval1_by = Auth::id();
        $movement->aprobacion_1 = now();
        $movement->aprobado_por_1 = Auth::user()->name ?? 'Sistema';
        $movement->save();

        Log::info('✅ Movimiento creado', [
            'movement_id' => $movement->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description
        ]);

        return $movement->id;
    }

    /**
     * Formatea moneda para logs
     */
    private function formatCurrency($amount, $currency)
    {
        $symbol = ($currency === 'PEN' || $currency === 'S/') ? 'S/' : 'US$';
        return $symbol . ' ' . number_format($amount, 2);
    }

    /**
     * Actualiza el balance del inversionista según el tipo de pago
     * 
     * Los valores vienen en SOLES desde el frontend
     * El modelo Balance maneja la conversión a centavos automáticamente
     */
    private function actualizarBalanceSegunTipo($investorId, $currency, $capital, $retornoBruto, $totalAPagar, $payType)
    {
        try {
            $balance = Balance::where('investor_id', $investorId)
                ->where('currency', $currency)
                ->first();

            if (!$balance) {
                throw new Exception("No se encontró balance para el inversionista");
            }

            Log::info('🎯 ACTUALIZANDO BALANCE', [
                'tipo_pago' => $payType,
                'capital' => $capital,
                'retorno_bruto' => $retornoBruto,
                'total_a_pagar' => $totalAPagar,
            ]);

            // Obtener valores actuales usando los accessors (devuelven decimales en SOLES)
            $amountActual = $balance->amount;
            $investedActual = $balance->invested_amount;
            $expectedActual = $balance->expected_amount;

            Log::info('📊 BALANCE ACTUAL', [
                'amount' => $this->formatCurrency($amountActual, $currency),
                'invested' => $this->formatCurrency($investedActual, $currency),
                'expected' => $this->formatCurrency($expectedActual, $currency)
            ]);

            // Calcular nuevos valores según tipo de pago
            $nuevoInvested = $investedActual;
            $nuevoExpected = $expectedActual;
            $nuevoAmount = $amountActual;

            switch ($payType) {
                case 'partial':
                    // PAGO PARCIAL: Solo se reduce el capital invertido
                    $nuevoInvested = max(0, $investedActual - $capital);
                    $nuevoAmount = $amountActual + $totalAPagar; // Se suma el capital devuelto
                    // Expected NO cambia porque no se pagaron intereses
                    break;

                case 'intereses':
                    // PAGO DE INTERESES: Solo se reduce el expected, capital invertido NO cambia
                    $nuevoExpected = max(0, $expectedActual - $retornoBruto);
                    $nuevoAmount = $amountActual + $totalAPagar; // Se suma el retorno neto (después de recaudación)
                    // Invested NO cambia porque no se devolvió capital
                    break;

                case 'total':
                    // PAGO TOTAL: Se reduce tanto el capital como el expected
                    $nuevoInvested = max(0, $investedActual - $capital);
                    $nuevoExpected = max(0, $expectedActual - $retornoBruto);
                    $nuevoAmount = $amountActual + $totalAPagar;
                    break;
            }

            Log::info('🧮 NUEVOS VALORES CALCULADOS', [
                'tipo_pago' => $payType,
                'nuevo_invested' => $this->formatCurrency($nuevoInvested, $currency),
                'nuevo_expected' => $this->formatCurrency($nuevoExpected, $currency),
                'nuevo_amount' => $this->formatCurrency($nuevoAmount, $currency),
            ]);

            // Actualizar balance - los mutators convierten automáticamente a centavos
            $balance->update([
                'invested_amount' => $nuevoInvested,
                'expected_amount' => $nuevoExpected,
                'amount' => $nuevoAmount
            ]);

            // Verificar valores guardados
            $balanceActualizado = $balance->fresh();
            Log::info('✅ Balance actualizado correctamente', [
                'investor_id' => $investorId,
                'tipo_pago' => $payType,
                'valores_guardados' => [
                    'invested' => $this->formatCurrency($balanceActualizado->invested_amount, $currency),
                    'expected' => $this->formatCurrency($balanceActualizado->expected_amount, $currency),
                    'amount' => $this->formatCurrency($balanceActualizado->amount, $currency)
                ]
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error al actualizar balance', [
                'investor_id' => $investorId,
                'tipo_pago' => $payType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Actualiza el statusPago de la factura según el estado de todos sus investments
     */
    private function actualizarEstadoFacturaDirecto($invoice, $payType)
    {
        $estadosInvestments = $invoice->investments->pluck('status')->toArray();

        $totalInvestments = count($estadosInvestments);
        $countPaid = 0;
        $countIntereses = 0;
        $countActive = 0;

        foreach ($estadosInvestments as $estado) {
            switch ($estado) {
                case 'paid':
                    $countPaid++;
                    break;
                case 'intereses':
                    $countIntereses++;
                    break;
                case 'active':
                case 'inactive':
                case 'pending':
                    $countActive++;
                    break;
            }
        }

        Log::info('📊 Análisis de estados de investments', [
            'invoice_id' => $invoice->id,
            'total' => $totalInvestments,
            'paid' => $countPaid,
            'intereses' => $countIntereses,
            'active/pending' => $countActive
        ]);

        // Determinar el statusPago de la factura
        if ($countPaid === $totalInvestments) {
            $invoice->statusPago = 'paid';
        } elseif ($countIntereses === $totalInvestments) {
            $invoice->statusPago = 'intereses';
        } elseif (($countPaid + $countIntereses) > 0 && ($countPaid + $countIntereses) === $totalInvestments) {
            $invoice->statusPago = 'intereses';
        } elseif ($countIntereses > 0 || $countPaid > 0) {
            $invoice->statusPago = 'intereses';
        } else {
            $invoice->statusPago = null;
        }

        Log::info('✅ Estado de factura actualizado', [
            'invoice_id' => $invoice->id,
            'statusPago_anterior' => $invoice->getOriginal('statusPago'),
            'statusPago_nuevo' => $invoice->statusPago ?? 'null (pendiente)',
            'pay_type' => $payType
        ]);
    }

    /**
     * Crea movimientos según el tipo de pago
     * 
     * TIPOS DE PAGO Y SUS MOVIMIENTOS:
     * 
     * 1. PAGO PARCIAL (partial):
     *    - 1 movimiento: Devolución parcial de capital (NO hay intereses, NO hay recaudación)
     * 
     * 2. PAGO DE INTERESES (intereses):
     *    - 2 movimientos: 
     *      a) Pago de retorno esperado (ganancia)
     *      b) Descuento 5% (recaudación)
     *    - NO se devuelve capital
     * 
     * 3. PAGO TOTAL (total):
     *    - 2 movimientos:
     *      a) Devolución de capital completo
     *      b) Pago de retorno esperado (ganancia completa SIN recaudación)
     * 
     * 4. PAGO ADELANTADO (cualquier tipo pero antes del vencimiento):
     *    - 3 movimientos:
     *      a) Devolución de capital
     *      b) Pago de retorno recalculado (proporcional a días invertidos)
     *      c) Descuento 5% sobre el retorno recalculado
     */
    

    /**
     * Método auxiliar para crear un movimiento
     */
    

    /**
     * Formatea moneda para logs
     */
 

    /**
     * Actualiza el balance del inversionista según el tipo de pago
     * 
     * Los valores vienen en SOLES desde el frontend
     * El modelo Balance maneja la conversión a centavos automáticamente
     */
 

    /**
     * Actualiza el statusPago de la factura según el estado de todos sus investments
     */


    /**
     * Crea los 3 movimientos requeridos para cada inversionista
     * IMPORTANTE: Los valores vienen en SOLES desde el frontend (50.00, 0.80)
     * El modelo Movement ya maneja la conversión a centavos automáticamente
     */
    private function crearMovimientosInversionista($investment, $capital, $retornoBruto, $recaudacion, $payType, $payment)
    {
        $movimientosCreados = [];

        try {
            Log::info('📥 VALORES RECIBIDOS DESDE FRONTEND (decimales en SOLES)', [
                'capital' => $capital,
                'retorno_bruto' => $retornoBruto,
                'recaudacion' => $recaudacion,
                'investor_id' => $investment->investor_id
            ]);

            // ===== MOVIMIENTO 1: DEVOLUCIÓN DE CAPITAL =====
            if ($capital > 0) {
                $movimientoCapital = new Movement();
                $movimientoCapital->id = \Illuminate\Support\Str::ulid();
                $movimientoCapital->currency = $investment->currency;
                // ⚠️ NO multiplicar por 100 - el modelo lo hace automáticamente
                $movimientoCapital->amount = $capital; // 50.00 soles
                $movimientoCapital->type = 'fixed_rate_capital_return';
                $movimientoCapital->status = MovementStatus::CONFIRMED;
                $movimientoCapital->confirm_status = MovementStatus::CONFIRMED;
                $movimientoCapital->description = "Devolución de capital - Inversión #{$investment->id} - Monto: S/ " . number_format($capital, 2);
                $movimientoCapital->origin = 'inversionista';
                $movimientoCapital->investor_id = $investment->investor_id;
                $movimientoCapital->approval1_by = Auth::id();
                $movimientoCapital->aprobacion_1 = now();
                $movimientoCapital->aprobado_por_1 = Auth::user()->name ?? 'Sistema';
                $movimientoCapital->save();

                $movimientosCreados[] = $movimientoCapital->id;

                Log::info('✅ Movimiento 1 - Capital creado', [
                    'movement_id' => $movimientoCapital->id,
                    'amount_enviado' => $capital,
                    'amount_guardado_raw' => $movimientoCapital->getAttributes()['amount'],
                    'amount_leido_accessor' => $movimientoCapital->amount
                ]);
            }

            // ===== MOVIMIENTO 2: PAGO DE RETORNO ESPERADO (GANANCIA) =====
            if ($retornoBruto > 0) {
                $movimientoRetorno = new Movement();
                $movimientoRetorno->id = \Illuminate\Support\Str::ulid();
                $movimientoRetorno->currency = $investment->currency;
                // ⚠️ NO multiplicar por 100 - el modelo lo hace automáticamente
                $movimientoRetorno->amount = $retornoBruto; // 0.80 soles
                $movimientoRetorno->type = 'fixed_rate_interest_payment';
                $movimientoRetorno->status = MovementStatus::CONFIRMED;
                $movimientoRetorno->confirm_status = MovementStatus::CONFIRMED;
                $movimientoRetorno->description = "Pago de retorno esperado - Inversión #{$investment->id} - Monto: S/ " . number_format($retornoBruto, 2);
                $movimientoRetorno->origin = 'inversionista';
                $movimientoRetorno->investor_id = $investment->investor_id;
                $movimientoRetorno->approval1_by = Auth::id();
                $movimientoRetorno->aprobacion_1 = now();
                $movimientoRetorno->aprobado_por_1 = Auth::user()->name ?? 'Sistema';
                $movimientoRetorno->save();

                $movimientosCreados[] = $movimientoRetorno->id;

                Log::info('✅ Movimiento 2 - Retorno creado', [
                    'movement_id' => $movimientoRetorno->id,
                    'amount_enviado' => $retornoBruto,
                    'amount_guardado_raw' => $movimientoRetorno->getAttributes()['amount'],
                    'amount_leido_accessor' => $movimientoRetorno->amount
                ]);
            }

            // ===== MOVIMIENTO 3: DESCUENTO DEL 5% (RECAUDACIÓN/COMISIÓN) =====
            if ($recaudacion > 0) {
                $movimientoDescuento = new Movement();
                $movimientoDescuento->id = \Illuminate\Support\Str::ulid();
                $movimientoDescuento->currency = $investment->currency;
                // ⚠️ NO multiplicar por 100 - el modelo lo hace automáticamente
                $movimientoDescuento->amount = $recaudacion;
                $movimientoDescuento->type = 'tax';
                $movimientoDescuento->status = MovementStatus::CONFIRMED;
                $movimientoDescuento->confirm_status = MovementStatus::CONFIRMED;
                $movimientoDescuento->description = "Descuento 5% sobre retorno - Inversión #{$investment->id} - Monto: S/ " . number_format($recaudacion, 2);
                $movimientoDescuento->origin = 'inversionista';
                $movimientoDescuento->investor_id = $investment->investor_id;
                $movimientoDescuento->approval1_by = Auth::id();
                $movimientoDescuento->aprobacion_1 = now();
                $movimientoDescuento->aprobado_por_1 = Auth::user()->name ?? 'Sistema';
                $movimientoDescuento->save();

                $movimientosCreados[] = $movimientoDescuento->id;

                Log::info('✅ Movimiento 3 - Descuento creado', [
                    'movement_id' => $movimientoDescuento->id,
                    'amount_enviado' => $recaudacion,
                    'amount_guardado_raw' => $movimientoDescuento->getAttributes()['amount'],
                    'amount_leido_accessor' => $movimientoDescuento->amount
                ]);
            }

            Log::info('🎉 RESUMEN DE MOVIMIENTOS CREADOS', [
                'investor_id' => $investment->investor_id,
                'total_movimientos' => count($movimientosCreados),
                'movement_ids' => $movimientosCreados,
                'valores_originales' => [
                    'capital' => $capital,
                    'retorno_bruto' => $retornoBruto,
                    'recaudacion' => $recaudacion
                ]
            ]);

            return $movimientosCreados;
        } catch (Exception $e) {
            Log::error('❌ Error al crear movimientos', [
                'investment_id' => $investment->id,
                'investor_id' => $investment->investor_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Actualiza el balance del inversionista
     * CORREGIDO: Los valores vienen en SOLES desde el frontend
     * El modelo Balance maneja la conversión a centavos automáticamente (igual que Movement)
     */
    private function actualizarBalanceDirecto($investorId, $currency, $capital, $retornoBruto, $totalAPagar)
    {
        try {
            $balance = Balance::where('investor_id', $investorId)
                ->where('currency', $currency)
                ->first();

            if (!$balance) {
                throw new Exception("No se encontró balance para el inversionista");
            }

            Log::info('🎯 VALORES RECIBIDOS (decimales en SOLES)', [
                'capital' => $capital,
                'retorno_bruto' => $retornoBruto,
                'total_a_pagar' => $totalAPagar,
            ]);

            // Obtener valores actuales usando los accessors (devuelven decimales en SOLES)
            $amountActual = $balance->amount; // Ya en SOLES (ej: 1000.00)
            $investedActual = $balance->invested_amount; // Ya en SOLES (ej: 800.00)
            $expectedActual = $balance->expected_amount; // Ya en SOLES (ej: 16.00)

            Log::info('📊 BALANCE ACTUAL (leído con accessors)', [
                'amount' => 'S/ ' . number_format($amountActual, 2),
                'invested' => 'S/ ' . number_format($investedActual, 2),
                'expected' => 'S/ ' . number_format($expectedActual, 2)
            ]);

            // Calcular nuevos valores directamente en SOLES
            $nuevoInvested = max(0, $investedActual - $capital);
            $nuevoExpected = max(0, $expectedActual - $retornoBruto);
            $nuevoAmount = $amountActual + $totalAPagar;

            Log::info('🧮 NUEVOS VALORES CALCULADOS (en SOLES)', [
                'nuevo_invested' => 'S/ ' . number_format($nuevoInvested, 2),
                'nuevo_expected' => 'S/ ' . number_format($nuevoExpected, 2),
                'nuevo_amount' => 'S/ ' . number_format($nuevoAmount, 2),
            ]);

            // Actualizar balance - los mutators convierten automáticamente a centavos
            $balance->update([
                'invested_amount' => $nuevoInvested,  // NO multiplicar - el mutator lo hace
                'expected_amount' => $nuevoExpected,  // NO multiplicar - el mutator lo hace
                'amount' => $nuevoAmount              // NO multiplicar - el mutator lo hace
            ]);

            // Verificar valores guardados
            $balanceActualizado = $balance->fresh();
            Log::info('✅ Balance actualizado correctamente', [
                'investor_id' => $investorId,
                'valores_guardados' => [
                    'invested' => 'S/ ' . number_format($balanceActualizado->invested_amount, 2),
                    'expected' => 'S/ ' . number_format($balanceActualizado->expected_amount, 2),
                    'amount' => 'S/ ' . number_format($balanceActualizado->amount, 2)
                ]
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error al actualizar balance', [
                'investor_id' => $investorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Actualiza el estado de la factura basado en los estados de sus investments
     * Maneja la reprogramación de facturas e investments
     */
    private function handleReprogramacion($invoice, $payDate, $reprogramationDate, $rate)
    {
        if (!$reprogramationDate) {
            throw new Exception("Fecha de reprogramación es requerida");
        }

        Log::info('🔄 Iniciando reprogramación', [
            'invoice_id' => $invoice->id,
            'fecha_reprogramacion' => $reprogramationDate,
            'nueva_tasa' => $rate
        ]);

        foreach ($invoice->investments as $investment) {
            // Verificar si ya existe una reprogramación para este investment
            $existeReprogramacion = DB::table('investments')
                ->where('previous_investment_id', $investment->id)
                ->where('status', 'reprogramed')
                ->exists();

            if ($existeReprogramacion) {
                Log::info('⚠️ Ya existe reprogramación para investment', [
                    'investment_id' => $investment->id
                ]);
                continue;
            }

            // Crear nuevo investment reprogramado
            $nuevaInversion = $investment->replicate();
            $nuevaInversion->status = 'reprogramed';
            $nuevaInversion->due_date = $reprogramationDate;
            $nuevaInversion->rate = $rate ?? $investment->rate;
            $nuevaInversion->previous_investment_id = $investment->id;
            $nuevaInversion->original_investment_id = $investment->original_investment_id ?? $investment->id;
            $nuevaInversion->created_at = now();
            $nuevaInversion->updated_at = now();
            $nuevaInversion->save();

            // Actualizar investment original
            $investment->comment = "Reprogramado el " . now()->format('d/m/Y') . " para " . Carbon::parse($reprogramationDate)->format('d/m/Y');
            $investment->status = 'reprogramed';
            $investment->save();

            Log::info('✅ Investment reprogramado', [
                'investment_original_id' => $investment->id,
                'investment_nuevo_id' => $nuevaInversion->id,
                'nueva_fecha_vencimiento' => $reprogramationDate
            ]);
        }

        // Actualizar estado de la factura
        $invoice->statusPago = 'reprogramed';

        Log::info('✅ Factura reprogramada', [
            'invoice_id' => $invoice->id,
            'statusPago' => $invoice->statusPago
        ]);
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
