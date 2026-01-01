<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GuardiansTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'first_name',
            'last_name',
            'phone',
            'email',
            'relationship',
            'occupation',
            'address',
            'student_admission_number',
            'is_primary',
            'is_emergency_contact',
        ];
    }

    public function array(): array
    {
        return [
            ['James', 'Doe', '+260971234567', 'james.doe@email.com', 'father', 'Engineer', '123 Main St', 'ADM-2024-001', 'true', 'true'],
            ['Mary', 'Doe', '+260972345678', 'mary.doe@email.com', 'mother', 'Teacher', '123 Main St', 'ADM-2024-001', 'false', 'false'],
            ['Robert', 'Smith', '+260973456789', 'robert.smith@email.com', 'guardian', 'Businessman', '456 Oak Ave', 'ADM-2024-002', 'true', 'true'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
        ];
    }
}
