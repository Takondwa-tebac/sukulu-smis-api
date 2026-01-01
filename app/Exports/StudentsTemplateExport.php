<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'first_name',
            'last_name',
            'middle_name',
            'gender',
            'date_of_birth',
            'admission_number',
            'nationality',
            'religion',
            'address',
            'medical_conditions',
        ];
    }

    public function array(): array
    {
        return [
            ['John', 'Doe', 'Michael', 'male', '2010-05-15', 'ADM-2024-001', 'Zambian', 'Christian', '123 Main St', ''],
            ['Jane', 'Smith', '', 'female', '2011-03-22', 'ADM-2024-002', 'Zambian', 'Muslim', '456 Oak Ave', 'Asthma'],
            ['Peter', 'Banda', 'James', 'male', '2010-08-10', '', 'Zambian', '', '789 Pine Rd', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
        ];
    }
}
