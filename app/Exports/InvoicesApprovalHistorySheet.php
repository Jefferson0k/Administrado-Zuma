<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class InvoicesApprovalHistorySheet implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    private $histories;

    public function __construct($histories)
    {
        $this->histories = $histories;
    }

    /**
     * Retorna la colección ya mapeada con el orden exacto de columnas requerido,
     * incluyendo el código de la factura relacionada como primera columna.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->histories->map(function ($item) {
            return [
                // Código de la factura relacionada
                $item->invoice->codigo ?? '-',

                // Creador (puede venir como created_by_name o relación creator)
                $item->created_by_name ?? optional($item->creator)->name ?? '-',

                // Actualizado por
                optional($item->updatedBy)->name ?? '-',

                // Fecha Actualización (usar fecha_actualizacion si existe, si no updated_at)
                $this->formatDate($item->fecha_actualizacion ?? $item->updated_at ?? null),

                // 1° Estado
                $this->niceStatus($item->approval1_status),

                // 1ª Por
                optional($item->approval1By)->name ?? '-',

                // 1ª Fecha
                $this->formatDate($item->approval1_at ?? null),

                // Comentario 1ª
                $item->approval1_comment ?? '-',

                // 2ª Estado
                $this->niceStatus($item->approval2_status),

                // 2ª Por
                optional($item->approval2By)->name ?? '-',

                // 2ª Fecha
                $this->formatDate($item->approval2_at ?? null),

                // Comentario 2ª
                $item->approval2_comment ?? '-',

                // Conclusión
                $this->niceStatus($item->status_conclusion),

                // Conclusión por
                optional($item->approvalConclusionBy)->name ?? '-',

                // Fecha conclusión
                $this->formatDate($item->approval_conclusion_at ?? null),

                // Comentario conclusión
                $item->approval_conclusion_comment ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Código Factura',
            'Creador',
            'Actualizado por',
            'Fecha Actualización',
            '1° Estado',
            '1ª Por',
            '1ª Fecha',
            'Comentario 1ª',
            '2ª Estado',
            '2ª Por',
            '2ª Fecha',
            'Comentario 2ª',
            'Conclusión',
            'Conclusión por',
            'Fecha conclusión',
            'Comentario conclusión',
        ];
    }

    public function title(): string
    {
        return 'Aprobaciones';
    }

    /**
     * Formatea fecha a d-m-Y H:i:s, devuelve '-' si es null/invalid.
     */
    private function formatDate($value)
    {
        if (!$value) return '-';

        try {
            return Carbon::parse($value)->format('d-m-Y H:i:s');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    /**
     * Mapea estados crudos a etiquetas legibles (puedes ampliar).
     */
    private function niceStatus($status)
    {
        if (!$status) return '-';

        return match ($status) {
            'approved' => 'Aprobado',
            'rejected' => 'Rechazado',
            'pending'  => 'Pendiente',
            'reprogramed' => 'Reprogramado',
            default => ucfirst((string) $status),
        };
    }
}
