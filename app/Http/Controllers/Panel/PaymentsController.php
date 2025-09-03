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

        foreach (array_slice($sheet, 1) as $row) {
            $rowData = array_combine($headers, $row);

            $document     = trim(strval($rowData['document'] ?? ''));
            $rucClient    = trim(strval($rowData['RUC_client'] ?? ''));
            $loanNumber   = trim(strval($rowData['loan_number'] ?? ''));
            $invoiceNum   = trim(strval($rowData['invoice_number'] ?? ''));
            $currencyExcel = strtoupper(trim(strval($rowData['currency'] ?? '')));
            $amountExcel   = floatval($rowData['amount'] ?? 0);

            // Fecha en formato Excel (dd/mm/yyyy)
            $estimatedPayDateExcel = strval($rowData['estimated_pay_date'] ?? '');
            $fechaExcelFormatted = null;
            if (!empty($estimatedPayDateExcel)) {
                $fechaParts = explode('/', $estimatedPayDateExcel);
                if (count($fechaParts) === 3) {
                    $fechaExcelFormatted = $fechaParts[2] . '-' . str_pad($fechaParts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($fechaParts[0], 2, '0', STR_PAD_LEFT);
                }
            }

            // Buscar en BD
            $company = Company::where('document', $document)->first();
            $invoice = $company ? $company->invoices()->where('RUC_client', $rucClient)->first() : null;

            $detalle = [];
            $estado  = 'Coincide';

            if (!$company) {
                $estado = 'No coincide';
                $detalle[] = "Empresa no registrada (document: '{$document}')";
            } elseif (!$invoice) {
                $estado = 'No coincide';
                $detalle[] = "Factura no encontrada (RUC Cliente: '{$rucClient}')";
            } else {
                // Comparación Monto
                $amountInvoice = floatval($invoice->amount);
                if (abs($amountInvoice - $amountExcel) < 0.01) {
                    $detalle[] = 'Monto: OK';
                } else {
                    $detalle[] = "Monto: Diferente (BD: {$amountInvoice} vs Excel: {$amountExcel})";
                    $estado = 'No coincide';
                }

                // Comparación Fecha estimada
                if ($fechaExcelFormatted && $invoice->estimated_pay_date === $fechaExcelFormatted) {
                    $detalle[] = 'Fecha estimada: OK';
                } else {
                    $detalle[] = "Fecha estimada: Diferente (BD: {$invoice->estimated_pay_date} vs Excel: {$fechaExcelFormatted})";
                    $estado = 'No coincide';
                }

                // Comparación Moneda
                $currencyInvoice = strtoupper($invoice->currency);
                if ($currencyInvoice === $currencyExcel) {
                    $detalle[] = 'Moneda: OK';
                } else {
                    $detalle[] = "Moneda: Diferente (BD: {$currencyInvoice} vs Excel: {$currencyExcel})";
                    $estado = 'No coincide';
                }

                // Comparación Invoice Number
                if ($invoice->invoice_number === $invoiceNum) {
                    $detalle[] = 'Nro Factura: OK';
                } else {
                    $detalle[] = "Nro Factura: Diferente (BD: {$invoice->invoice_number} vs Excel: {$invoiceNum})";
                    $estado = 'No coincide';
                }

                // Comparación Loan Number (si existe en el modelo)
                if (property_exists($invoice, 'loan_number') && $invoice->loan_number === $loanNumber) {
                    $detalle[] = 'Nro Préstamo: OK';
                } else {
                    $detalle[] = "Nro Préstamo: Diferente (BD: {$invoice->loan_number} vs Excel: {$loanNumber})";
                    $estado = 'No coincide';
                }

                $detalle[] = "DEBUG: ID Factura: {$invoice->id}";
            }

            $rowData['estado']  = $estado;
            $rowData['detalle'] = implode(' | ', $detalle);

            $jsonData[] = $rowData;
        }

        return response()->json([
            'success' => true,
            'data' => $jsonData,
        ]);
    }
}
