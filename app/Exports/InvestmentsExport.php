<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

use App\Models\Investment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class InvestmentsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected string $razonSocial;
    protected $currency; // null|string
    protected array $statuses; // normalizado a array
    protected string $codigo;

    public function __construct(string $razonSocial = '', $currency = null, string $status = '', string $codigo = '')
    {
        $this->razonSocial = trim($razonSocial);
        $this->currency    = $currency !== null && $currency !== '' ? $currency : null;
        $this->statuses    = $this->normalizeStatuses($status);
        $this->codigo      = trim($codigo);
    }

    protected function normalizeStatuses(string $statusCsv): array
    {
        if ($statusCsv === '') return [];
        // admite CSV o un solo valor
        return array_values(array_filter(array_map(
            fn ($s) => strtolower(trim($s)),
            explode(',', $statusCsv)
        )));
    }

    public function query()
    {
        $q = Investment::query()
            ->with([
                'investor:id,name,document',
                'invoice:id,codigo,company_id',
                'invoice.company:id,name',
            ])
            ->orderByDesc('created_at');

        // Filtro por razón social (company.razon_social)
        if ($this->razonSocial !== '') {
            $rs = $this->razonSocial;
            $q->whereHas('invoice.company', function (Builder $qq) use ($rs) {
                $qq->where('name', 'like', "%{$rs}%");
            });
        }

        // Filtro por moneda exacta
        if ($this->currency !== null) {
            $q->where('currency', $this->currency);
        }

        // Filtro por status (uno o varios)
        if (!empty($this->statuses)) {
            $q->whereIn('status', $this->statuses);
        }

        // Filtro por código (invoice.codigo)
        if ($this->codigo !== '') {
            $code = $this->codigo;
            $q->whereHas('invoice', function (Builder $qq) use ($code) {
                $qq->where('codigo', 'like', "%{$code}%");
            });
        }

        return $q;
    }

    public function headings(): array
    {
        return [
            'Código',            // invoice.codigo
            'Inversionista',     // investor.name
            'DNI',               // investor.document
            'Razón Social',      // invoice.company.razon_social
            'Monto',             // amount
            'Retorno',           // return
            'Tasa (%)',          // rate
            'Moneda',            // currency
            'Fecha Vencimiento', // due_date
            'Estado',            // status (ES)
            'Creación',          // created_at
        ];
    }

    public function map($investment): array
    {
        return [
            optional($investment->invoice)->codigo ?? '-',
            optional($investment->investor)->name ?? '-',
            optional($investment->investor)->document ?? '-',
            optional(optional($investment->invoice)->company)->name ?? '-',
            $this->formatNumber($investment->amount),
            $this->formatNumber($investment->return),
            $this->formatRate($investment->rate),
            $investment->currency ?? '-',
            $this->formatDate($investment->due_date),
            $this->statusText($investment->status),
            $this->formatDate($investment->created_at),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // bold en headers
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        // evitar wrap innecesario
        $sheet->getStyle('A:K')->getAlignment()->setWrapText(false);
        return [];
    }

    /* ===== Helpers ===== */

    protected function formatNumber($value): string
    {
        if ($value === null || $value === '') return '-';
        return number_format((float) $value, 2, '.', ' ');
    }

    protected function formatRate($value): string
    {
        if ($value === null || $value === '') return '-';
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') ?: '0';
    }

    protected function formatDate($value): string
    {
        if (empty($value)) return '-';
        $ts = strtotime((string) $value);
        if ($ts === false) return '-';
        return date('d/m/Y', $ts);
    }

    protected function statusText(?string $status): string
    {
        $map = [
            'inactive'    => 'Inactivo',
            'active'      => 'Activo',
            'expired'     => 'Vencido',
            'judicialized'=> 'Judicializado',
            'reprogramed' => 'Reprogramado',
            'paid'        => 'Pagado',
            'canceled'    => 'Cancelado',
            'dastandby'   => 'En Standby',
        ];
        $key = strtolower((string) $status);
        return $map[$key] ?? ($status ?: '-');
    }
}