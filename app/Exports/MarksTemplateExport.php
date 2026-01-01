<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MarksTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'admission_number',
            'score',
            'remarks',
        ];
    }

    public function array(): array
    {
        return [
            ['ADM-2024-001', 85, 'Excellent performance'],
            ['ADM-2024-002', 72, 'Good effort'],
            ['ADM-2024-003', 58, 'Needs improvement'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
        ];
    }
}
