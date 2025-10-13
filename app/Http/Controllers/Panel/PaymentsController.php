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
        $request->validate([
            "amount_to_be_paid" => "required|numeric",
            "pay_date" => "required|string",
            "pay_type" => "required|in:total,partial,intereses,reprogramacion",
            "reprogramation_date" => "nullable|string",
            "reprogramation_rate" => "nullable|numeric",
        ]);
        
        DB::beginTransaction();
        try {
            $payDate = Carbon::createFromFormat('d/m/Y', $request->pay_date)->format('Y-m-d');
            $reprogramationDate = $request->reprogramation_date 
                ? Carbon::createFromFormat('d/m/Y', $request->reprogramation_date)->format('Y-m-d')
                : null;
                
            $invoice = Invoice::with(['investments' => function($query) {
                $query->whereNotIn('status', ['paid', 'reprogramed']);
            }])->findOrFail($invoiceId);
            
            $amountToProcess = (float) $request->amount_to_be_paid;
            
            if ($invoice->investments->isEmpty()) {
                throw new Exception('No hay inversiones activas asociadas a esta factura');
            }

            // Calcular total pendiente para validación
            $totales = $this->calcularTotalesDistribucion($invoice->investments, $request->pay_type);
            $totalPendiente = $totales['total'];
            
            Log::info('Validación de montos', [
                'monto_a_pagar' => $amountToProcess,
                'total_pendiente' => $totalPendiente,
                'pay_type' => $request->pay_type
            ]);

            if ($amountToProcess > $totalPendiente) {
                throw new Exception("Monto a pagar ({$amountToProcess}) excede el total pendiente ({$totalPendiente})");
            }

            $payment = new Payment();
            $payment->invoice_id = $invoice->id;
            $payment->pay_type = $request->pay_type;
            $payment->amount_to_be_paid = (int) round($amountToProcess * 100);
            $payment->pay_date = $payDate;
            $payment->reprogramation_date = $reprogramationDate;
            $payment->reprogramation_rate = $request->reprogramation_rate ?? 0;
            $payment->approval1_status = 'approved';
            $payment->approval1_by = Auth::id();
            $payment->approval1_at = now();
            $payment->approval1_comment = 'Aprobado automáticamente por instancia 1';
            
            // Arrays para almacenar el historial
            $investmentDetails = [];
            $recaudacionDetails = [];
            $movementsCreated = [];

            $dueDate = Carbon::parse($invoice->due_date);
            $paymentDate = Carbon::parse($payDate);
            $isAdelantado = $paymentDate->lt($dueDate);
            
            if ($isAdelantado) {
                $invoice->fecha_pagoadelantado = $payDate;
                $this->handlePagoAdelantado($invoice, $payDate);
            }

            if ($request->pay_type === 'reprogramacion') {
                $this->handleReprogramacion($invoice, $payDate, $reprogramationDate, $request->reprogramation_rate);
                $payment->save();
                DB::commit();
                return response()->json([
                    "message" => "Reprogramación procesada correctamente",
                    "payment" => $payment,
                    "invoice" => $invoice,
                ]);
            }

            // Procesar distribución y capturar historial
            $this->distribuirPago($invoice, $payment, $amountToProcess, $request->pay_type, $investmentDetails, $recaudacionDetails, $movementsCreated);
            
            // Guardar el pago
            $payment->save();

            $this->actualizarEstadoFactura($invoice, $request->pay_type);
            
            DB::commit();
            
            return response()->json([
                "message" => "Pago procesado correctamente",
                "payment" => $payment,
                "distribucion" => $investmentDetails,
                "recaudacion" => $recaudacionDetails,
                "movements" => $movementsCreated,
                "invoice" => $invoice,
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

    private function distribuirPago($invoice, $payment, $amountToProcess, $payType, &$investmentDetails, &$recaudacionDetails, &$movementsCreated)
    {
        $montoRestante = $amountToProcess;
        $investments = $invoice->investments;
        
        $totales = $this->calcularTotalesDistribucion($investments, $payType);
        $totalToDistribute = $totales['total'];
        $basesCalculo = $totales['bases'];

        foreach ($investments as $index => $investment) {
            if ($montoRestante <= 0) break;

            $baseCalculo = $basesCalculo[$investment->id] ?? 0;
            
            if ($baseCalculo <= 0) {
                continue;
            }

            $proporcion = $totalToDistribute > 0 ? $baseCalculo / $totalToDistribute : 0;
            
            $montoAsignado = $index === count($investments) - 1 
                ? $montoRestante 
                : round($amountToProcess * $proporcion, 2);
                
            $montoAsignado = min($montoAsignado, $baseCalculo);
            
            if ($montoAsignado <= 0) continue;

            // Procesar pago y capturar detalles
            $detalleInversion = $this->procesarPagoInversion($investment, $montoAsignado, $payType, $payment);
            
            // CREAR MOVIMIENTO Y ACTUALIZAR BALANCE - CORREGIDO
            $movement = $this->crearMovimientoYBalance($investment, $montoAsignado, $payType, $payment);
            if ($movement) {
                $movementsCreated[] = $movement->id;
            }
            
            // Guardar en historial
            $investmentDetails[] = [
                'investment_id' => $investment->id,
                'investor_id' => $investment->investor_id,
                'investor_name' => $investment->inversionista ?? 'Inversionista',
                'monto_asignado' => $montoAsignado,
                'tipo_pago' => $payType,
                'capital_antes' => $detalleInversion['capital_antes'] ?? 0,
                'capital_despues' => $detalleInversion['capital_despues'] ?? 0,
                'intereses_antes' => $detalleInversion['intereses_antes'] ?? 0,
                'intereses_despues' => $detalleInversion['intereses_despues'] ?? 0,
                'estado_antes' => $detalleInversion['estado_antes'] ?? 'active',
                'estado_despues' => $detalleInversion['estado_despues'] ?? 'active',
                'recaudacion_aplicada' => $detalleInversion['recaudacion_aplicada'] ?? 0,
                'movement_id' => $movement ? $movement->id : null,
                'timestamp' => now()->toISOString()
            ];

            // Guardar recaudación si existe
            if (($detalleInversion['monto_recaudacion'] ?? 0) > 0) {
                $recaudacionDetails[] = [
                    'investment_id' => $investment->id,
                    'investor_id' => $investment->investor_id,
                    'monto_recaudacion' => $detalleInversion['monto_recaudacion'] ?? 0,
                    'porcentaje_recaudacion' => $detalleInversion['porcentaje_recaudacion'] ?? 0,
                    'timestamp' => now()->toISOString()
                ];
            }

            $montoRestante -= $montoAsignado;
        }

        if ($montoRestante > 0) {
            $this->redistribuirMontoRestante($invoice, $payment, $montoRestante, $payType, $investmentDetails, $recaudacionDetails, $movementsCreated);
        }

        $invoice->paid_amount += (int) round($amountToProcess * 100);
        $invoice->save();
    }

    private function crearMovimientoYBalance($investment, $montoAsignado, $payType, $payment)
    {
        try {
            Log::info('Creando movimiento y balance', [
                'investment_id' => $investment->id,
                'monto_asignado' => $montoAsignado,
                'pay_type' => $payType
            ]);

            // Determinar el tipo de movimiento según el tipo de pago
            $tipoMovimiento = $this->getTipoMovimiento($payType);
            
            // Crear el movimiento
            $movement = new Movement();
            $movement->id = \Illuminate\Support\Str::ulid();
            $movement->amount = $montoAsignado;
            $movement->type = $tipoMovimiento;
            $movement->currency = $investment->currency;
            $movement->status = MovementStatus::CONFIRMED;
            $movement->confirm_status = MovementStatus::CONFIRMED;
            $movement->description = $this->getDescripcionMovimiento($payType, $investment, $montoAsignado);
            $movement->origin = 'inversionista';
            $movement->investor_id = $investment->investor_id;
            $movement->approval1_by = Auth::id();
            $movement->aprobacion_1 = now();
            $movement->aprobado_por_1 = Auth::user()->name ?? 'Sistema';
            $movement->save();

            Log::info('Movimiento creado', [
                'movement_id' => $movement->id,
                'type' => $tipoMovimiento,
                'amount' => $montoAsignado
            ]);

            // Actualizar el balance del inversionista
            $this->actualizarBalanceInversionista($investment->investor_id, $investment->currency, $montoAsignado, $payType);

            return $movement;

        } catch (Exception $e) {
            Log::error('Error al crear movimiento y balance', [
                'investment_id' => $investment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function getTipoMovimiento($payType)
    {
        return match($payType) {
            'intereses' => 'fixed_rate_interest_payment',
            'partial' => 'fixed_rate_capital_return',
            'total' => 'fixed_rate_capital_return',
            default => 'payment'
        };
    }

    private function getDescripcionMovimiento($payType, $investment, $montoAsignado)
    {
        $descripciones = [
            'intereses' => "Pago de intereses de inversión #{$investment->id} - Monto: {$montoAsignado}",
            'partial' => "Devolución parcial de capital - Inversión #{$investment->id} - Monto: {$montoAsignado}",
            'total' => "Devolución total de capital - Inversión #{$investment->id} - Monto: {$montoAsignado}"
        ];

        return $descripciones[$payType] ?? "Pago de inversión #{$investment->id} - Monto: {$montoAsignado}";
    }

    private function actualizarBalanceInversionista($investorId, $currency, $montoAsignado, $payType)
    {
        try {
            Log::info('Actualizando balance', [
                'investor_id' => $investorId,
                'currency' => $currency,
                'monto_asignado' => $montoAsignado,
                'pay_type' => $payType
            ]);

            // Buscar o crear balance del inversionista
            $balance = Balance::firstOrCreate(
                [
                    'investor_id' => $investorId,
                    'currency' => $currency
                ],
                [
                    'amount' => 0,
                    'invested_amount' => 0,
                    'expected_amount' => 0,
                    'id' => \Illuminate\Support\Str::ulid()
                ]
            );

            // Convertir a centavos para la actualización
            $montoCentavos = (int) round($montoAsignado * 100);

            Log::info('Balance antes de actualizar', [
                'amount_antes' => $balance->amount,
                'invested_amount_antes' => $balance->invested_amount,
                'expected_amount_antes' => $balance->expected_amount
            ]);

            // Actualizar balances según el tipo de pago
            if ($payType === 'intereses') {
                // Para intereses: aumentar el amount disponible
                $balance->amount += $montoCentavos;
            } else {
                // Para capital: aumentar amount y disminuir invested_amount
                $balance->amount += $montoCentavos;
                $balance->invested_amount = max(0, $balance->invested_amount - $montoCentavos);
            }

            $balance->save();

            Log::info('Balance actualizado correctamente', [
                'investor_id' => $investorId,
                'currency' => $currency,
                'monto_agregado' => $montoCentavos,
                'pay_type' => $payType,
                'nuevo_amount' => $balance->amount,
                'nuevo_invested_amount' => $balance->invested_amount,
                'nuevo_expected_amount' => $balance->expected_amount
            ]);

        } catch (Exception $e) {
            Log::error('Error al actualizar balance', [
                'investor_id' => $investorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // Los demás métodos se mantienen igual...
    private function procesarPagoInversion($investment, $montoAsignado, $payType, $payment)
    {
        $returnEfectivizadoActual = ((int)($investment->return_efectivizado ?? 0)) / 100;
        $returnTotal = ((int)($investment->return ?? 0)) / 100;
        $amountTotal = ((int)($investment->amount ?? 0)) / 100;
        $recaudacionPorcentaje = ((int)($investment->recaudacion ?? 5)) / 100;

        // Capturar estado antes del pago
        $estadoAntes = $investment->status;
        $capitalAntes = $amountTotal;
        $interesesAntes = $returnEfectivizadoActual;

        $montoRecaudacion = 0;

        switch ($payType) {
            case 'intereses':
                $montoRecaudacion = $this->pagarIntereses($investment, $montoAsignado, $recaudacionPorcentaje);
                break;
                
            case 'partial':
                $montoRecaudacion = $this->pagarParcial($investment, $montoAsignado, $recaudacionPorcentaje);
                break;
                
            case 'total':
                $montoRecaudacion = $this->pagarTotal($investment, $recaudacionPorcentaje);
                break;
        }

        $investment->save();

        // Retornar detalles para el historial
        return [
            'capital_antes' => $capitalAntes,
            'capital_despues' => ((int)($investment->amount ?? 0)) / 100,
            'intereses_antes' => $interesesAntes,
            'intereses_despues' => ((int)($investment->return_efectivizado ?? 0)) / 100,
            'estado_antes' => $estadoAntes,
            'estado_despues' => $investment->status,
            'recaudacion_aplicada' => $recaudacionPorcentaje * 100,
            'monto_recaudacion' => $montoRecaudacion,
            'porcentaje_recaudacion' => $recaudacionPorcentaje * 100
        ];
    }

    private function pagarIntereses($investment, $monto, $recaudacionPorcentaje)
    {
        $returnEfectivizadoDecimal = ((int)($investment->return_efectivizado ?? 0)) / 100;
        $returnTotal = ((int)($investment->return ?? 0)) / 100;
        
        $montoEfectivo = $monto * (1 - $recaudacionPorcentaje);
        $montoRecaudacion = $monto * $recaudacionPorcentaje;
        
        $nuevoReturnEfectivizado = $returnEfectivizadoDecimal + $montoEfectivo;
        
        $investment->return_efectivizado = (int) round($nuevoReturnEfectivizado * 100);
        
        if ($nuevoReturnEfectivizado >= $returnTotal) {
            $investment->status = 'intereses';
        } else {
            $investment->status = 'active';
        }

        return $montoRecaudacion;
    }

    private function pagarParcial($investment, $monto, $recaudacionPorcentaje)
    {
        $returnEfectivizadoDecimal = ((int)($investment->return_efectivizado ?? 0)) / 100;
        $returnTotal = ((int)($investment->return ?? 0)) / 100;
        $returnPendiente = max(0, $returnTotal - $returnEfectivizadoDecimal);
        $amountTotal = ((int)($investment->amount ?? 0)) / 100;

        $montoRecaudacion = 0;

        if ($monto <= $returnPendiente) {
            $montoEfectivo = $monto * (1 - $recaudacionPorcentaje);
            $montoRecaudacion = $monto * $recaudacionPorcentaje;
            
            $investment->return_efectivizado = (int) round(($returnEfectivizadoDecimal + $montoEfectivo) * 100);
            
            $investment->status = 'active';
        } else {
            $montoRetorno = $returnPendiente;
            $montoCapital = $monto - $returnPendiente;
            
            $montoEfectivoRetorno = $montoRetorno * (1 - $recaudacionPorcentaje);
            $montoRecaudacion = $montoRetorno * $recaudacionPorcentaje;
            
            $investment->return_efectivizado = (int) round(($returnEfectivizadoDecimal + $montoEfectivoRetorno) * 100);
            
            $nuevoCapital = max(0, $amountTotal - $montoCapital);
            $investment->amount = (int) round($nuevoCapital * 100);
            
            if ($investment->amount <= 0 && $investment->return_efectivizado >= (int) round($returnTotal * 100)) {
                $investment->status = 'paid';
            } else {
                $investment->status = 'active';
            }
        }

        return $montoRecaudacion;
    }

    private function pagarTotal($investment, $recaudacionPorcentaje)
    {
        $returnTotal = ((int)($investment->return ?? 0)) / 100;
        
        $returnEfectivo = $returnTotal * (1 - $recaudacionPorcentaje);
        $montoRecaudacion = $returnTotal * $recaudacionPorcentaje;
        
        $investment->return_efectivizado = (int) round($returnEfectivo * 100);
        $investment->amount = 0;
        $investment->status = 'paid';

        return $montoRecaudacion;
    }

    private function calcularTotalesDistribucion($investments, $payType)
    {
        $total = 0;
        $basesCalculo = [];
        
        foreach ($investments as $investment) {
            $returnDecimal = ((int)($investment->return ?? 0)) / 100;
            $returnEfectivizadoDecimal = ((int)($investment->return_efectivizado ?? 0)) / 100;
            $amountDecimal = ((int)($investment->amount ?? 0)) / 100;
            
            switch ($payType) {
                case 'intereses':
                    $base = max(0, $returnDecimal - $returnEfectivizadoDecimal);
                    break;
                case 'partial':
                case 'total':
                    $returnPendiente = max(0, $returnDecimal - $returnEfectivizadoDecimal);
                    $base = $amountDecimal + $returnPendiente;
                    break;
                default:
                    $base = 0;
            }
            
            $basesCalculo[$investment->id] = $base;
            $total += $base;
        }
        
        return ['total' => $total, 'bases' => $basesCalculo];
    }

    private function redistribuirMontoRestante($invoice, $payment, $montoRestante, $payType, &$investmentDetails, &$recaudacionDetails, &$movementsCreated)
    {
        $investments = $invoice->investments;
        
        foreach ($investments as $investment) {
            if ($montoRestante <= 0) break;
            
            $returnDecimal = ((int)($investment->return ?? 0)) / 100;
            $returnEfectivizadoDecimal = ((int)($investment->return_efectivizado ?? 0)) / 100;
            $amountDecimal = ((int)($investment->amount ?? 0)) / 100;
            
            $returnPendiente = max(0, $returnDecimal - $returnEfectivizadoDecimal);
            $capitalPendiente = $amountDecimal;
            
            $maximoAsignable = 0;
            switch ($payType) {
                case 'intereses':
                    $maximoAsignable = $returnPendiente;
                    break;
                case 'partial':
                case 'total':
                    $maximoAsignable = $capitalPendiente + $returnPendiente;
                    break;
            }
            
            if ($maximoAsignable > 0) {
                $montoAsignar = min($montoRestante, $maximoAsignable);
                $detalleInversion = $this->procesarPagoInversion($investment, $montoAsignar, $payType, $payment);
                
                // CREAR MOVIMIENTO Y ACTUALIZAR BALANCE - CORREGIDO
                $movement = $this->crearMovimientoYBalance($investment, $montoAsignar, $payType, $payment);
                if ($movement) {
                    $movementsCreated[] = $movement->id;
                }
                
                // Guardar en historial
                $investmentDetails[] = [
                    'investment_id' => $investment->id,
                    'investor_id' => $investment->investor_id,
                    'investor_name' => $investment->inversionista ?? 'Inversionista',
                    'monto_asignado' => $montoAsignar,
                    'tipo_pago' => $payType,
                    'capital_antes' => $detalleInversion['capital_antes'] ?? 0,
                    'capital_despues' => $detalleInversion['capital_despues'] ?? 0,
                    'intereses_antes' => $detalleInversion['intereses_antes'] ?? 0,
                    'intereses_despues' => $detalleInversion['intereses_despues'] ?? 0,
                    'estado_antes' => $detalleInversion['estado_antes'] ?? 'active',
                    'estado_despues' => $detalleInversion['estado_despues'] ?? 'active',
                    'recaudacion_aplicada' => $detalleInversion['recaudacion_aplicada'] ?? 0,
                    'movement_id' => $movement ? $movement->id : null,
                    'timestamp' => now()->toISOString()
                ];

                if (($detalleInversion['monto_recaudacion'] ?? 0) > 0) {
                    $recaudacionDetails[] = [
                        'investment_id' => $investment->id,
                        'investor_id' => $investment->investor_id,
                        'monto_recaudacion' => $detalleInversion['monto_recaudacion'] ?? 0,
                        'porcentaje_recaudacion' => $detalleInversion['porcentaje_recaudacion'] ?? 0,
                        'timestamp' => now()->toISOString()
                    ];
                }

                $montoRestante -= $montoAsignar;
            }
        }
    }

    private function actualizarEstadoFactura($invoice, $payType)
    {
        $totalAdeudadoCents = 0;
        $todosPagados = true;
        $todosIntereses = true;
        
        foreach ($invoice->investments as $investment) {
            $amountCents = (int)($investment->amount ?? 0);
            $returnCents = (int)($investment->return ?? 0);
            $returnEfectivizadoCents = (int)($investment->return_efectivizado ?? 0);
            
            $capitalPendiente = $amountCents;
            $retornoPendiente = max(0, $returnCents - $returnEfectivizadoCents);
            
            $totalAdeudadoCents += $capitalPendiente + $retornoPendiente;
            
            if ($investment->status !== 'paid') {
                $todosPagados = false;
            }
            if ($investment->status !== 'intereses' && $investment->status !== 'paid') {
                $todosIntereses = false;
            }
        }

        // LÓGICA CORREGIDA - Si es pago parcial, siempre poner como reprogramed
        if ($payType === 'partial') {
            $invoice->statusPago = 'reprogramed';
        } else if ($todosPagados) {
            $invoice->statusPago = 'paid';
        } else if ($todosIntereses || $invoice->paid_amount > 0) {
            $invoice->statusPago = 'intereses';
        } else {
            $invoice->statusPago = 'reprogramed';
        }
        
        $invoice->save();
    }

    private function handlePagoAdelantado($invoice, $payDate)
    {
        Log::info('Manejando pago adelantado', [
            'invoice_id' => $invoice->id,
            'pay_date' => $payDate,
            'due_date' => $invoice->due_date
        ]);
        
        foreach ($invoice->investments as $investment) {
            $diasAdelanto = Carbon::parse($invoice->due_date)->diffInDays($payDate);
            
            if ($diasAdelanto > 0) {
                $investment->comment = ($investment->comment ? $investment->comment . " | " : "") . 
                                     "Pago adelantado con {$diasAdelanto} días de anticipación";
                $investment->save();
            }
        }
    }

    private function handleReprogramacion($invoice, $payDate, $reprogramationDate, $rate)
    {
        if (!$reprogramationDate) {
            throw new Exception("Fecha de reprogramación es requerida para este tipo de pago");
        }

        foreach ($invoice->investments as $investment) {
            $existeReprogramacion = DB::table('investments')
                ->where('previous_investment_id', $investment->id)
                ->where('status', 'reprogramed')
                ->exists();

            if ($existeReprogramacion) {
                continue;
            }

            $nuevaInversion = $investment->replicate();
            $nuevaInversion->status = 'reprogramed';
            $nuevaInversion->due_date = $reprogramationDate;
            $nuevaInversion->rate = $rate ?? $investment->rate;
            $nuevaInversion->previous_investment_id = $investment->id;
            $nuevaInversion->original_investment_id = $investment->original_investment_id ?? $investment->id;
            $nuevaInversion->created_at = now();
            $nuevaInversion->updated_at = now();
            $nuevaInversion->save();

            $investment->comment = "Reprogramado el " . now()->format('d/m/Y') . " para " . Carbon::parse($reprogramationDate)->format('d/m/Y');
            $investment->status = 'reprogramed';
            $investment->save();
        }

        $invoice->statusPago = 'reprogramed';
        $invoice->save();
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
