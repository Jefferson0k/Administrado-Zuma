<?php

namespace App\Exports;

use App\Http\Resources\Factoring\Invoice\InvoiceResource;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoicesExport implements FromCollection, WithHeadings{
    private $invoices;
    public function __construct($invoices){
        $this->invoices = $invoices;
    }
    public function collection(){
        $data = collect(InvoiceResource::collection($this->invoices)->resolve());
        return $data->map(function ($item) {
            return [
                $item['razonSocial'] ?? '',
                $item['codigo'] ?? '',
                $item['moneda'] ?? '',
                $item['montoFactura'] ?? '',
                $item['montoAsumidoZuma'] ?? '',
                $item['montoDisponible'] ?? '',
                $item['tasa'] ?? '',
                $item['fechaPago'] ?? '',
                $item['fechaCreacion'] ?? '',
                $item['estado'] ?? '',
            ];
        });
    }
    public function headings(): array{
        return [
            'Razón Social',
            'Código',
            'Moneda',
            'Monto Factura',
            'Monto Asumido Zuma',
            'Monto Disponible',
            'Tasa',
            'Fecha de Pago',
            'Fecha de Creación',
            'Estado',
        ];
    }
}
