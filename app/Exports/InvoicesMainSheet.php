<?php

namespace App\Exports;

use App\Http\Resources\Factoring\Invoice\InvoiceResource;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class InvoicesMainSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    private $invoices;

    public function __construct($invoices)
    {
        $this->invoices = $invoices;
    }

    public function collection()
    {
        $data = collect(InvoiceResource::collection($this->invoices)->resolve());

        return $data->map(function ($item) {
            return [
                $item['razonSocial'] ?? '',
                $item['ruc_cliente'] ?? '',
                $item['ruc_proveedor'] ?? '',
                $item['codigo'] ?? '',
                $item['moneda'] ?? '',
                $item['montoFactura'] ?? '',
                $item['montoAsumidoZuma'] ?? '',
                $item['montoDisponible'] ?? '',
                $item['tasa'] ?? '',
                $item['PrimerStado'] ?? '',
                $item['userprimerNombre'] ?? '',
                $item['tiempoUno'] ?? '',
                $item['SegundaStado'] ?? '',
                $item['userdosNombre'] ?? '',
                $item['tiempoDos'] ?? '',
                $item['estado'] ?? '',
                $item['condicionOportunidadInversion'] ?? '',
                $item['tipo'] ?? '',
                $item['situacion'] ?? '',
                $item['fechaHoraCierreInversion'] ?? '',
                $item['fechaPago'] ?? '',
                $item['porcentajeMetaTerceros'] ?? '',
                $item['porcentajeInversionTerceros'] ?? '',
                $item['fechaCreacion'] ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Razón Social',
            'RUC Cliente',
            'RUC Proveedor',
            'Código',
            'Moneda',
            'Monto Factura',
            'Monto Asumido Zuma',
            'Monto Disponible',
            'Tasa (%)',
            '1ª Aprobador',
            '1ª Usuario',
            'T. 1ª Aprobación',
            '2ª Aprobador',
            '2º Usuario',
            'T. 2ª Aprobación',
            'Estado Conclusión',
            'Cond. Oportunidad de Inversión',
            'Tipo',
            'Situación',
            'Fecha y Hora Cierre de Inversión',
            'Fecha de Pago',
            '% Obj Terceros',
            '% Invertido Terceros',
            'Fecha Creación',
        ];
    }

    public function title(): string
    {
        return 'Facturas';
    }
}
