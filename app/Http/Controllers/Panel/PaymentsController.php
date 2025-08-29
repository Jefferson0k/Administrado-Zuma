<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelType;

class PaymentsController extends Controller
{
    public function comparacion(Request $request)
    {
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
        $jsonData = [];

        // Procesar desde la fila 2 (índice 1)
        foreach (array_slice($sheet, 1) as $row) {
            $rowData = array_combine($headers, $row);
            
            // ======= Extraer datos del Excel con nuevos campos =======
            $document = trim(strval($rowData['document'] ?? ''));
            $rucClient = trim(strval($rowData['RUC_client'] ?? ''));
            $estimatedPayDateExcel = strval($rowData['estimated_pay_date'] ?? '');
            $currencyExcel = strtoupper(trim(strval($rowData['currency'] ?? '')));
            $amountExcel = floatval($rowData['amount'] ?? 0);

            // ======= Convertir fecha de Excel (dd/mm/yyyy) a formato BD (yyyy-mm-dd) =======
            $fechaExcelFormatted = null;
            if (!empty($estimatedPayDateExcel)) {
                $fechaParts = explode('/', $estimatedPayDateExcel);
                if (count($fechaParts) === 3) {
                    $fechaExcelFormatted = $fechaParts[2] . '-' . str_pad($fechaParts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($fechaParts[0], 2, '0', STR_PAD_LEFT);
                }
            }

            // ======= Buscar compañía e invoice =======
            $company = Company::where('document', $document)->first();
            $invoice = $company ? $company->invoices()->where('RUC_client', $rucClient)->first() : null;

            $detalle = [];
            $estado = 'Coincide';

            if (!$company) {
                $estado = 'No coincide';
                $detalle[] = "Empresa no registrada (Buscando documento: '{$document}')";
            } elseif (!$invoice) {
                $estado = 'No coincide';
                $detalle[] = "Factura no encontrada (RUC Cliente: '{$rucClient}')";
            } else {
                // ======= Comparar datos =======
                
                // Monto (usando el getter que convierte de centavos a decimales)
                $amountInvoice = floatval($invoice->amount);
                if (abs($amountInvoice - $amountExcel) < 0.01) {
                    $detalle[] = 'Monto: OK';
                } else {
                    $detalle[] = "Monto: Diferente (BD: {$amountInvoice} vs Excel: {$amountExcel})";
                    $estado = 'No coincide';
                }

                // Fecha estimada de pago
                if ($fechaExcelFormatted && $invoice->estimated_pay_date === $fechaExcelFormatted) {
                    $detalle[] = 'Fecha estimada: OK';
                } else {
                    $detalle[] = "Fecha estimada: Diferente (BD: {$invoice->estimated_pay_date} vs Excel: {$fechaExcelFormatted})";
                    $estado = 'No coincide';
                }

                // Moneda
                $currencyInvoice = strtoupper($invoice->currency);
                if ($currencyInvoice === $currencyExcel) {
                    $detalle[] = 'Moneda: OK';
                } else {
                    $detalle[] = "Moneda: Diferente (BD: {$currencyInvoice} vs Excel: {$currencyExcel})";
                    $estado = 'No coincide';
                }
                
                // Debug info
                $detalle[] = "DEBUG: ID Factura: {$invoice->id}";
            }

            $rowData['estado'] = $estado;
            $rowData['detalle'] = implode(' | ', $detalle);
            $jsonData[] = $rowData;
        }

        return response()->json([
            'success' => true,
            'data' => $jsonData,
        ]);
    }
}