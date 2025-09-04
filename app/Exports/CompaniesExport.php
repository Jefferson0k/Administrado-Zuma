<?php
namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CompaniesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $companies;

    // Mapeo de riesgos numéricos a letras
    protected $riskMapping = [
        0 => 'A',
        1 => 'B', 
        2 => 'C',
        3 => 'D',
        4 => 'E'
    ];

    public function __construct($companies)
    {
        $this->companies = $companies;
    }

    public function collection()
    {
        return $this->companies;
    }

    public function map($company): array
    {
        return [
            $company->document,
            $company->name,
            $company->business_name,
            $this->getRiskLabel($company->risk),
            $company->sector?->name ?? 'N/A',
            $company->subsector?->name ?? 'N/A',
            $company->created_at
                ? Carbon::parse($company->created_at)->format('d/m/Y H:i:s')
                : 'N/A',
        ];
    }

    public function headings(): array
    {
        return [
            'Documento',
            'Razón Social', 
            'Nombre Comercial',
            'Riesgo',
            'Sector',
            'Subsector',
            'Fecha Creación'
        ];
    }

    /**
     * Convertir valor numérico de riesgo a letra
     */
    protected function getRiskLabel($riskValue)
    {
        return $this->riskMapping[$riskValue] ?? 'N/A';
    }

    /**
     * Configurar anchos de columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Documento
            'B' => 35,  // Razón Social
            'C' => 30,  // Nombre Comercial
            'D' => 10,  // Riesgo
            'E' => 20,  // Sector
            'F' => 25,  // Subsector
            'G' => 20,  // Fecha Creación
        ];
    }

    /**
     * Aplicar estilos al Excel
     */
    public function styles(Worksheet $sheet)
    {
        $totalRows = $this->companies->count() + 1; // +1 para incluir encabezados

        return [
            // Estilo para encabezados (fila 1)
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                    'name' => 'Arial'
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E75B6'], // Azul profesional
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],

            // Estilo para todas las filas de datos
            "A2:G{$totalRows}" => [
                'font' => [
                    'size' => 10,
                    'name' => 'Arial'
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ],

            // Estilo específico para la columna de Documento (centrado)
            "A2:A{$totalRows}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],

            // Estilo específico para la columna de Riesgo (centrado y con color según valor)
            "D2:D{$totalRows}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'font' => [
                    'bold' => true,
                    'size' => 11,
                ],
            ],

            // Filas pares con fondo ligeramente gris para mejor legibilidad
            "A2:G2" => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']]],
            "A4:G4" => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']]],
            "A6:G6" => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']]],
            "A8:G8" => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']]],
            "A10:G10" => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']]],
        ];
    }
}