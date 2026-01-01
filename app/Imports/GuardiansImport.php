<?php

namespace App\Imports;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class GuardiansImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected string $schoolId;

    public array $results = [
        'created' => 0,
        'updated' => 0,
        'linked' => 0,
        'failed' => 0,
        'errors' => [],
    ];

    public function __construct(string $schoolId)
    {
        $this->schoolId = $schoolId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $data = $this->normalizeRow($row);

                $validator = Validator::make($data, [
                    'first_name' => ['required', 'string', 'max:100'],
                    'last_name' => ['required', 'string', 'max:100'],
                    'relationship' => ['required', 'string', 'in:father,mother,guardian,uncle,aunt,grandparent,sibling,other'],
                    'phone' => ['nullable', 'string', 'max:20'],
                    'email' => ['nullable', 'email', 'max:255'],
                ]);

                if ($validator->fails()) {
                    $this->results['failed']++;
                    $this->results['errors'][] = [
                        'row' => $rowNumber,
                        'errors' => $validator->errors()->toArray(),
                        'data' => $data,
                    ];
                    continue;
                }

                // Check if guardian exists by email or phone
                $existingGuardian = null;
                if (!empty($data['email'])) {
                    $existingGuardian = Guardian::where('school_id', $this->schoolId)
                        ->where('email', $data['email'])
                        ->first();
                }
                if (!$existingGuardian && !empty($data['phone'])) {
                    $existingGuardian = Guardian::where('school_id', $this->schoolId)
                        ->where('phone', $data['phone'])
                        ->first();
                }

                if ($existingGuardian) {
                    $existingGuardian->update([
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'occupation' => $data['occupation'] ?? $existingGuardian->occupation,
                        'address' => $data['address'] ?? $existingGuardian->address,
                    ]);
                    $guardian = $existingGuardian;
                    $this->results['updated']++;
                } else {
                    $guardian = Guardian::create([
                        'school_id' => $this->schoolId,
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'phone' => $data['phone'] ?? null,
                        'email' => $data['email'] ?? null,
                        'occupation' => $data['occupation'] ?? null,
                        'address' => $data['address'] ?? null,
                        'is_active' => true,
                    ]);
                    $this->results['created']++;
                }

                // Link to student if admission number provided
                if (!empty($data['student_admission_number'])) {
                    $student = Student::where('school_id', $this->schoolId)
                        ->where('admission_number', $data['student_admission_number'])
                        ->first();

                    if ($student) {
                        $student->guardians()->syncWithoutDetaching([
                            $guardian->id => [
                                'relationship' => $data['relationship'],
                                'is_primary' => $data['is_primary'] ?? false,
                                'is_emergency_contact' => $data['is_emergency_contact'] ?? false,
                                'receives_reports' => true,
                                'receives_invoices' => true,
                            ],
                        ]);
                        $this->results['linked']++;
                    }
                }
            } catch (\Exception $e) {
                $this->results['failed']++;
                $this->results['errors'][] = [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                    'data' => $row->toArray(),
                ];
            }
        }
    }

    protected function normalizeRow($row): array
    {
        $relationship = strtolower($row['relationship'] ?? $row['relation'] ?? 'guardian');
        
        return [
            'first_name' => $row['first_name'] ?? $row['firstname'] ?? null,
            'last_name' => $row['last_name'] ?? $row['lastname'] ?? $row['surname'] ?? null,
            'phone' => $row['phone'] ?? $row['phone_number'] ?? $row['mobile'] ?? $row['tel'] ?? null,
            'email' => $row['email'] ?? $row['email_address'] ?? null,
            'relationship' => $relationship,
            'occupation' => $row['occupation'] ?? $row['job'] ?? null,
            'address' => $row['address'] ?? $row['home_address'] ?? null,
            'student_admission_number' => $row['student_admission_number'] ?? $row['student_adm_no'] ?? $row['child_adm_no'] ?? null,
            'is_primary' => filter_var($row['is_primary'] ?? $row['primary'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_emergency_contact' => filter_var($row['is_emergency_contact'] ?? $row['emergency'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
