<?php

namespace App\Exports;

use App\Models\HistoryAprobadorInvoice;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class InvoicesExport implements WithMultipleSheets
{
    use Exportable;

    private $invoices;

    public function __construct($invoices)
    {
        $this->invoices = $invoices;
    }

    public function sheets(): array
    {
        $sheets = [];

        // 1️⃣ Hoja principal de facturas
        $sheets['Facturas'] = new InvoicesMainSheet($this->invoices);

        // 2️⃣ Historial de aprobaciones relacionadas
        $invoiceIds = $this->invoices->pluck('id');
        $histories = HistoryAprobadorInvoice::with([
            'invoice:id,codigo', // ✅ Traemos el código de la factura
            'approval1By:id,name',
            'approval2By:id,name',
            'approvalConclusionBy:id,name',
            'updatedBy:id,name',
        ])
        ->whereIn('invoice_id', $invoiceIds)
        ->orderByDesc('id')
        ->get();

        $sheets['Aprobaciones'] = new InvoicesApprovalHistorySheet($histories);

        return $sheets;
    }
}
