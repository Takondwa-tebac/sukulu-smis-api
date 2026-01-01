<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class StudentsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected string $schoolId;
    protected ?string $academicYearId;
    protected ?string $classId;
    protected ?string $streamId;
    protected ?object $school;

    public array $results = [
        'created' => 0,
        'updated' => 0,
        'failed' => 0,
        'errors' => [],
    ];

    public function __construct(
        string $schoolId,
        ?string $academicYearId = null,
        ?string $classId = null,
        ?string $streamId = null,
        ?object $school = null
    ) {
        $this->schoolId = $schoolId;
        $this->academicYearId = $academicYearId;
        $this->classId = $classId;
        $this->streamId = $streamId;
        $this->school = $school;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because of header row and 0-index

            try {
                $data = $this->normalizeRow($row);

                $validator = Validator::make($data, [
                    'first_name' => ['required', 'string', 'max:100'],
                    'last_name' => ['required', 'string', 'max:100'],
                    'gender' => ['required', 'in:male,female,Male,Female,M,F'],
                    'date_of_birth' => ['nullable', 'date'],
                    'admission_number' => ['nullable', 'string', 'max:50'],
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

                // Normalize gender
                $gender = strtolower($data['gender']);
                if (in_array($gender, ['m', 'male'])) {
                    $gender = 'male';
                } elseif (in_array($gender, ['f', 'female'])) {
                    $gender = 'female';
                }

                // Check if student exists by admission number
                $existingStudent = null;
                if (!empty($data['admission_number'])) {
                    $existingStudent = Student::where('school_id', $this->schoolId)
                        ->where('admission_number', $data['admission_number'])
                        ->first();
                }

                if ($existingStudent) {
                    // Update existing student
                    $existingStudent->update([
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'middle_name' => $data['middle_name'] ?? null,
                        'gender' => $gender,
                        'date_of_birth' => $data['date_of_birth'] ?? null,
                        'nationality' => $data['nationality'] ?? null,
                        'religion' => $data['religion'] ?? null,
                        'address' => $data['address'] ?? null,
                        'medical_conditions' => $data['medical_conditions'] ?? null,
                    ]);
                    $this->results['updated']++;
                } else {
                    // Generate admission number if not provided
                    $admissionNumber = $data['admission_number'] ?? Student::generateAdmissionNumber($this->school);

                    $student = Student::create([
                        'school_id' => $this->schoolId,
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'middle_name' => $data['middle_name'] ?? null,
                        'gender' => $gender,
                        'date_of_birth' => $data['date_of_birth'] ?? null,
                        'admission_number' => $admissionNumber,
                        'admission_date' => now(),
                        'nationality' => $data['nationality'] ?? null,
                        'religion' => $data['religion'] ?? null,
                        'address' => $data['address'] ?? null,
                        'medical_conditions' => $data['medical_conditions'] ?? null,
                        'status' => Student::STATUS_ACTIVE,
                    ]);

                    // Create enrollment if class is specified
                    if ($this->academicYearId && $this->classId) {
                        StudentEnrollment::create([
                            'school_id' => $this->schoolId,
                            'student_id' => $student->id,
                            'academic_year_id' => $this->academicYearId,
                            'class_id' => $this->classId,
                            'stream_id' => $this->streamId,
                            'status' => 'active',
                            'enrolled_at' => now(),
                        ]);
                    }

                    $this->results['created']++;
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
        // Handle different possible column names
        return [
            'first_name' => $row['first_name'] ?? $row['firstname'] ?? $row['first'] ?? null,
            'last_name' => $row['last_name'] ?? $row['lastname'] ?? $row['surname'] ?? $row['last'] ?? null,
            'middle_name' => $row['middle_name'] ?? $row['middlename'] ?? $row['middle'] ?? null,
            'gender' => $row['gender'] ?? $row['sex'] ?? null,
            'date_of_birth' => $row['date_of_birth'] ?? $row['dob'] ?? $row['birth_date'] ?? $row['birthdate'] ?? null,
            'admission_number' => $row['admission_number'] ?? $row['adm_no'] ?? $row['admission_no'] ?? $row['student_id'] ?? null,
            'nationality' => $row['nationality'] ?? $row['country'] ?? null,
            'religion' => $row['religion'] ?? null,
            'address' => $row['address'] ?? $row['home_address'] ?? null,
            'medical_conditions' => $row['medical_conditions'] ?? $row['medical'] ?? $row['health'] ?? null,
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
