<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\StudentMark;
use App\Models\ExamSubject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class MarksImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected string $schoolId;
    protected string $examSubjectId;
    protected ExamSubject $examSubject;

    public array $results = [
        'created' => 0,
        'updated' => 0,
        'failed' => 0,
        'errors' => [],
    ];

    public function __construct(string $schoolId, string $examSubjectId)
    {
        $this->schoolId = $schoolId;
        $this->examSubjectId = $examSubjectId;
        $this->examSubject = ExamSubject::with('exam')->findOrFail($examSubjectId);
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $data = $this->normalizeRow($row);

                $validator = Validator::make($data, [
                    'admission_number' => ['required_without:student_id', 'string'],
                    'student_id' => ['required_without:admission_number', 'string'],
                    'score' => ['required', 'numeric', 'min:0', 'max:100'],
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

                // Find student by admission number or ID
                $student = null;
                if (!empty($data['student_id'])) {
                    $student = Student::where('school_id', $this->schoolId)
                        ->where('id', $data['student_id'])
                        ->first();
                }
                
                if (!$student && !empty($data['admission_number'])) {
                    $student = Student::where('school_id', $this->schoolId)
                        ->where('admission_number', $data['admission_number'])
                        ->first();
                }

                if (!$student) {
                    $this->results['failed']++;
                    $this->results['errors'][] = [
                        'row' => $rowNumber,
                        'error' => 'Student not found',
                        'data' => $data,
                    ];
                    continue;
                }

                // Check if mark already exists
                $existingMark = StudentMark::where('exam_subject_id', $this->examSubjectId)
                    ->where('student_id', $student->id)
                    ->first();

                if ($existingMark) {
                    $existingMark->update([
                        'score' => $data['score'],
                        'total_score' => $data['score'],
                        'remarks' => $data['remarks'] ?? $existingMark->remarks,
                    ]);
                    $this->results['updated']++;
                } else {
                    StudentMark::create([
                        'school_id' => $this->schoolId,
                        'exam_subject_id' => $this->examSubjectId,
                        'student_id' => $student->id,
                        'score' => $data['score'],
                        'total_score' => $data['score'],
                        'remarks' => $data['remarks'] ?? null,
                        'status' => StudentMark::STATUS_DRAFT,
                    ]);
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
        return [
            'student_id' => $row['student_id'] ?? $row['id'] ?? null,
            'admission_number' => $row['admission_number'] ?? $row['adm_no'] ?? $row['admission_no'] ?? null,
            'score' => $row['score'] ?? $row['mark'] ?? $row['marks'] ?? $row['grade'] ?? null,
            'remarks' => $row['remarks'] ?? $row['comment'] ?? $row['comments'] ?? null,
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
