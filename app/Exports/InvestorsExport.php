<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use App\Models\Investor;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvestorsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, Responsable

{
    use Exportable;

    private string $search;

    public $fileName = 'inversionistas.xlsx';

    public function __construct(?string $search = '')
    {
        $this->search = (string) $search;
    }

    public function headings(): array
    {
        return [
            'Nombre',
            'Documento',
            'Alias',
            'Teléfono',
            'Email',
            '1ª Estado',
            '1º Por',
            '1º Fecha',
            '1º Comentario',
            '2ª Estado',
            '2º Por',
            '2º Fecha',
            '2º Comentario',
            'Estado Conclusión',
            'Creación',
        ];
    }

    public function collection()
    {
        $q = Investor::query()
            // Si tienes relaciones para mostrar nombre del aprobador, descomenta:
            // ->with(['approval1By:id,name', 'approval2By:id,name'])
            ;

        if ($this->search !== '') {
            $term = '%' . Str::of($this->search)->lower() . '%';
            $q->where(function ($qq) use ($term) {
                $qq->whereRaw('LOWER(name) LIKE ?', [$term])
                   ->orWhereRaw('LOWER(alias) LIKE ?', [$term])
                   ->orWhereRaw('LOWER(email) LIKE ?', [$term])
                   ->orWhereRaw('LOWER(status) LIKE ?', [$term])
                   ->orWhereRaw('LOWER(document) LIKE ?', [$term]);
            });
        }

        // Ajusta el select si necesitas optimizar columnas
        return $q->orderByDesc('created_at')->get();
    }

    public function map($inv): array
    {
        // Si approval1_by / approval2_by son IDs y tienes relaciones approval1By/approval2By,
        // reemplaza por: $inv->approval1By?->name, $inv->approval2By?->name
        $approval1By = $inv->approval1By->name ?? $inv->approval1_by ?? null;
        $approval2By = $inv->approval2By->name ?? $inv->approval2_by ?? null;

        return [
            $inv->name ?? '',
            $inv->document ?? '',
            $inv->alias ?? '',
            $inv->telephone ?? '',
            $inv->email ?? '',
            $inv->approval1_status ?? '',
            $approval1By ?? '',
            optional($inv->approval1_at)->toDateTimeString() ?? ($inv->approval1_at ?? ''),
            $inv->approval1_comment ?? '',
            $inv->approval2_status ?? '',
            $approval2By ?? '',
            optional($inv->approval2_at)->toDateTimeString() ?? ($inv->approval2_at ?? ''),
            $inv->approval2_comment ?? '',
            $inv->status ?? '',
            optional($inv->created_at)->toDateTimeString() ?? ($inv->created_at ?? ''),
        ];
    }
}