<?php

namespace App\Exports;

use App\Http\Resources\Factoring\Deposit\DepositResource;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class DepositsExport implements FromCollection, WithHeadings, WithColumnWidths
{
    private $deposits;

    public function __construct($deposits)
    {
        $this->deposits = $deposits;
    }

    // Función para traducir estados
    private function traducirEstado(?string $estado): string
    {
        if (!$estado) return '';
        return match ($estado) {
            'valid' => 'Válido',
            'invalid' => 'Inválido',
            'pending' => 'Pendiente',
            'rejected' => 'Rechazado',
            'confirmed' => 'Confirmado',
            default => $estado,
        };
    }

    public function collection()
    {
        $data = collect(DepositResource::collection($this->deposits)->resolve());

        return $data->map(function ($item) {
            return [
                $item['investor'] ?? '',
                $item['nomBanco'] ?? '',
                $item['nro_operation'] ?? '',
                $item['currency'] ?? '',
                $item['amount'] ?? '',
                $this->traducirEstado($item['estado'] ?? ''),
                $item['fecha_aprobacion_1'] ?? '',
                $item['aprobado_por_1_nombre'] ?? '',
                $this->traducirEstado($item['estadoConfig'] ?? ''),
                $item['fecha_aprobacion_2'] ?? '',
                $item['aprobado_por_2_nombre'] ?? '',
                $item['creacion'] ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Inversor',
            'Banco',
            'Nº Operación',
            'Moneda',
            'Monto',
            '1ª Estado',
            'T. 1ª Aprobación',
            '1ª Usuario',
            '2ª Estado',
            'T. 2ª Aprobación',
            '2ª Usuario',
            'Fecha Creación',
        ];
    }

    // Anchos de columna más amplios
    public function columnWidths(): array
    {
        return [
            'A' => 30, // Inversor
            'B' => 25, // Banco
            'C' => 15, // Nº Operación
            'D' => 10, // Moneda
            'E' => 15, // Monto
            'F' => 15, // 1ª Estado
            'G' => 20, // T. 1ª Aprobación
            'H' => 30, // 1ª Usuario
            'I' => 15, // 2ª Estado
            'J' => 20, // T. 2ª Aprobación
            'K' => 30, // 2ª Usuario
            'L' => 20, // Fecha Creación
        ];
    }
}
