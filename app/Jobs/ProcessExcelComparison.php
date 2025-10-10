<?php

namespace App\Jobs;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelType;

class ProcessExcelComparison implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 60 minutos
    public $tries = 3;
    public $backoff = [60, 120, 300];

    protected $filePath;
    protected $userId;
    protected $resultFilePath;

    public function __construct($filePath, $userId = null)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->resultFilePath = 'excel-results/comparacion_' . now()->format('Ymd_His') . '.json';
    }

    public function handle()
    {
        try {
            \Log::info("Iniciando procesamiento de archivo: {$this->filePath}");

            // Verificar que el archivo existe
            if (!Storage::exists($this->filePath)) {
                throw new \Exception("Archivo no encontrado: {$this->filePath}");
            }

            $fullPath = Storage::path($this->filePath);
            
            // Validar que el archivo sea legible
            if (!is_readable($fullPath)) {
                throw new \Exception("Archivo no puede ser leído: {$this->filePath}");
            }

            // Cargar datos del Excel
            $data = Excel::toArray([], $fullPath, null, ExcelType::XLSX);
            
            if (empty($data) || empty($data[0])) {
                throw new \Exception('El archivo Excel está vacío o no pudo ser leído');
            }

            $sheet = $data[0];
            $headers = array_map('strval', $sheet[0]);
            
            // Validar columnas requeridas
            $requiredColumns = ['document', 'ruc_proveedor', 'invoice_number', 'loan_number', 
                               'estimated_pay_date', 'currency', 'amount', 'status', 'saldo'];
            
            $missingColumns = array_diff($requiredColumns, $headers);
            if (!empty($missingColumns)) {
                throw new \Exception('Faltan columnas requeridas: ' . implode(', ', $missingColumns));
            }

            // Pre-cargar datos de la base de datos
            $excelDocuments = array_unique(array_column(array_slice($sheet, 1), 0));
            $excelDocuments = array_filter(array_map('trim', $excelDocuments));
            
            $companies = Company::with(['invoices' => function($query) {
                $query->select(['id', 'company_id', 'ruc_proveedor', 'invoice_number', 'loan_number', 
                               'amount', 'estimated_pay_date', 'currency', 'status']);
            }])
            ->whereIn('document', $excelDocuments)
            ->get()
            ->keyBy('document');

            $jsonData = [];
            $processedRows = 0;
            $batchSize = 500;

            foreach (array_slice($sheet, 1) as $rowIndex => $row) {
                if (count($row) !== count($headers)) {
                    continue; // Saltar filas inconsistentes
                }

                $rowData = array_combine($headers, $row);
                
                // Normalizar datos
                $documentExcel = trim(strval($rowData['document'] ?? ''));
                $rucClientExcel = trim(strval($rowData['ruc_proveedor'] ?? ''));
                $invoiceNumberExcel = trim(strval($rowData['invoice_number'] ?? ''));
                $loanNumberExcel = trim(strval($rowData['loan_number'] ?? ''));
                $estimatedPayDateExcel = strval($rowData['estimated_pay_date'] ?? '');
                $currencyExcel = strtoupper(trim(strval($rowData['currency'] ?? '')));
                $amountExcel = floatval($rowData['amount'] ?? 0);
                $statusExcel = strtolower(trim(strval($rowData['status'] ?? '')));
                $saldoExcel = floatval($rowData['saldo'] ?? 0);

                $fechaExcelFormatted = $this->normalizeDate($estimatedPayDateExcel);

                $detalle = [];
                $estado = 'Coincide';
                $invoiceId = null;
                $company = null;
                $invoice = null;

                // Buscar coincidencias
                if (isset($companies[$documentExcel])) {
                    $company = $companies[$documentExcel];
                    
                    $invoice = $company->invoices->first(function($inv) use ($rucClientExcel, $invoiceNumberExcel, $loanNumberExcel) {
                        return $inv->ruc_proveedor === $rucClientExcel 
                            && $inv->invoice_number === $invoiceNumberExcel 
                            && $inv->loan_number === $loanNumberExcel;
                    });
                    
                    if ($invoice) {
                        $invoiceId = $invoice->id;
                        
                        // Realizar comparaciones
                        $this->realizarComparaciones($invoice, $company, $documentExcel, $rucClientExcel, 
                                                    $invoiceNumberExcel, $loanNumberExcel, $amountExcel,
                                                    $fechaExcelFormatted, $currencyExcel, $statusExcel, 
                                                    $detalle, $estado);
                    } else {
                        $estado = 'No coincide';
                        $detalle[] = "Factura no encontrada";
                    }
                } else {
                    $estado = 'No coincide';
                    $detalle[] = "Empresa no registrada";
                }

                // Determinar tipo de pago
                $tipoPago = $this->determinarTipoPago($saldoExcel, $amountExcel);

                $jsonData[] = [
                    'document' => $documentExcel,
                    'ruc_proveedor' => $rucClientExcel,
                    'invoice_number' => $invoiceNumberExcel,
                    'loan_number' => $loanNumberExcel,
                    'amount' => $amountExcel,
                    'saldo' => $saldoExcel,
                    'estado' => $estado,
                    'tipo_pago' => $tipoPago,
                    'id_pago' => $invoiceId,
                    'detalle' => implode(' | ', $detalle)
                ];

                $processedRows++;

                // Liberar memoria periódicamente
                if ($processedRows % $batchSize === 0) {
                    gc_collect_cycles();
                }
            }

            // Guardar resultados en archivo JSON
            $resultData = [
                'success' => true,
                'data' => $jsonData,
                'total_procesado' => $processedRows,
                'procesado_en' => now()->toDateTimeString()
            ];

            Storage::put($this->resultFilePath, json_encode($resultData, JSON_PRETTY_PRINT));

            // Limpiar archivo temporal
            Storage::delete($this->filePath);

            \Log::info("Procesamiento completado. Registros: {$processedRows}, Archivo: {$this->resultFilePath}");

        } catch (\Exception $e) {
            \Log::error("Error en ProcessExcelComparison: {$e->getMessage()}", [
                'file' => $this->filePath,
                'trace' => $e->getTraceAsString()
            ]);

            // Guardar resultado de error
            $errorResult = [
                'success' => false,
                'message' => 'Error procesando el archivo: ' . $e->getMessage(),
                'procesado_en' => now()->toDateTimeString()
            ];

            Storage::put($this->resultFilePath, json_encode($errorResult, JSON_PRETTY_PRINT));

            // Limpiar archivo temporal en caso de error
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }

            throw $e; // Relanzar para que Queue lo maneje
        }
    }

    private function realizarComparaciones($invoice, $company, $docExcel, $rucExcel, $invExcel, 
                                         $loanExcel, $amountExcel, $fechaExcel, $currencyExcel, 
                                         $statusExcel, &$detalle, &$estado)
    {
        $comparisons = [
            'Documento empresa' => [$company->document, $docExcel],
            'RUC Cliente' => [$invoice->ruc_proveedor, $rucExcel],
            'Nro Factura' => [$invoice->invoice_number, $invExcel],
            'Loan Number' => [$invoice->loan_number, $loanExcel],
        ];

        foreach ($comparisons as $field => [$dbValue, $excelValue]) {
            if ($dbValue === $excelValue) {
                $detalle[] = "{$field}: OK";
            } else {
                $detalle[] = "{$field}: Diferente (BD: {$dbValue} vs Excel: {$excelValue})";
                $estado = 'No coincide';
            }
        }

        // Comparar amount
        $amountInvoice = floatval($invoice->amount);
        if (abs($amountInvoice - $amountExcel) < 0.01) {
            $detalle[] = 'Monto: OK';
        } else {
            $detalle[] = "Monto: Diferente (BD: {$amountInvoice} vs Excel: {$amountExcel})";
            $estado = 'No coincide';
        }

        // Comparar fecha
        if ($invoice->estimated_pay_date && $fechaExcel) {
            $fechaBD = \Carbon\Carbon::parse($invoice->estimated_pay_date)->format('Y-m-d');
            if ($fechaBD === $fechaExcel) {
                $detalle[] = 'Fecha estimada: OK';
            } else {
                $detalle[] = "Fecha estimada: Diferente (BD: {$fechaBD} vs Excel: {$fechaExcel})";
                $estado = 'No coincide';
            }
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
    }

    private function normalizeDate($dateString)
    {
        if (empty($dateString)) return null;
        
        try {
            if (strpos($dateString, '/') !== false) {
                $parts = explode('/', $dateString);
                if (count($parts) === 3) {
                    return $parts[2] . '-' . 
                           str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . 
                           str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                }
            }
            return \Carbon\Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function determinarTipoPago($saldo, $amount)
    {
        if ($saldo > 0 && $amount > 0) {
            return abs($saldo - $amount) < 0.01 ? 'Pago normal' : 'Pago parcial';
        } elseif ($saldo == 0 && $amount > 0) {
            return 'Pago completado';
        }
        return 'Sin determinar';
    }

    public function failed(\Exception $exception)
    {
        \Log::error("Job ProcessExcelComparison falló: {$exception->getMessage()}");
        
        // Limpiar archivo temporal si existe
        if (Storage::exists($this->filePath)) {
            Storage::delete($this->filePath);
        }
    }
}