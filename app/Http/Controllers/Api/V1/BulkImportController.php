<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\GuardiansTemplateExport;
use App\Exports\MarksTemplateExport;
use App\Exports\StudentsTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\GuardiansImport;
use App\Imports\MarksImport;
use App\Imports\StudentsImport;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentMark;
use App\Models\ExamSubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group Data Import
 *
 * APIs for bulk importing data via CSV/Excel files for school data migration
 */
class BulkImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:students.import')->only(['importStudents', 'downloadStudentTemplate', 'getStudentTemplate']);
        $this->middleware('permission:marks.import')->only(['importMarks', 'downloadMarksTemplate', 'getMarksTemplate']);
        $this->middleware('permission:guardians.manage')->only(['importGuardians', 'downloadGuardiansTemplate', 'getGuardiansTemplate']);
    }

    /**
     * Import students from CSV/Excel
     *
     * Bulk import students from a CSV or Excel file. Existing students (matched by admission_number) will be updated.
     *
     * @authenticated
     * @bodyParam file file required CSV or Excel file (max 10MB). No-example
     * @bodyParam academic_year_id uuid Academic year for enrollment. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @bodyParam class_id uuid Class for enrollment. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @bodyParam stream_id uuid Stream for enrollment. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     *
     * @response 200 scenario="Success" {"message": "50 students created, 10 updated, 2 failed.", "created": 50, "updated": 10, "failed": 2, "errors": []}
     * @response 422 scenario="Validation Error" {"message": "The file field is required."}
     */
    public function importStudents(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required_without:students', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
            'students' => ['required_without:file', 'array', 'min:1', 'max:500'],
            'students.*.first_name' => ['required_with:students', 'string', 'max:100'],
            'students.*.last_name' => ['required_with:students', 'string', 'max:100'],
            'students.*.gender' => ['required_with:students', 'in:male,female'],
            'students.*.date_of_birth' => ['nullable', 'date'],
            'students.*.admission_number' => ['nullable', 'string', 'max:50'],
            'academic_year_id' => ['nullable', 'uuid', 'exists:academic_years,id'],
            'class_id' => ['nullable', 'uuid', 'exists:classes,id'],
            'stream_id' => ['nullable', 'uuid', 'exists:streams,id'],
        ]);

        $schoolId = $request->user()->school_id;
        $school = $request->user()->school;
        $academicYearId = $request->input('academic_year_id');
        $classId = $request->input('class_id');
        $streamId = $request->input('stream_id');

        // Handle file upload (CSV/Excel)
        if ($request->hasFile('file')) {
            $import = new StudentsImport($schoolId, $academicYearId, $classId, $streamId, $school);
            
            Excel::import($import, $request->file('file'));
            
            $results = $import->getResults();

            return response()->json([
                'message' => "{$results['created']} students created, {$results['updated']} updated, {$results['failed']} failed.",
                'created' => $results['created'],
                'updated' => $results['updated'],
                'failed' => $results['failed'],
                'errors' => $results['errors'],
            ]);
        }

        // Handle JSON array (legacy support)
        $results = DB::transaction(function () use ($request, $schoolId, $school, $academicYearId, $classId, $streamId) {
            $created = 0;
            $failed = 0;
            $errors = [];

            foreach ($request->students as $index => $studentData) {
                try {
                    $admissionNumber = $studentData['admission_number'] 
                        ?? Student::generateAdmissionNumber($school);

                    $student = Student::create([
                        'school_id' => $schoolId,
                        'first_name' => $studentData['first_name'],
                        'last_name' => $studentData['last_name'],
                        'middle_name' => $studentData['middle_name'] ?? null,
                        'gender' => $studentData['gender'],
                        'date_of_birth' => $studentData['date_of_birth'] ?? null,
                        'admission_number' => $admissionNumber,
                        'admission_date' => now(),
                        'status' => Student::STATUS_ACTIVE,
                    ]);

                    $studentClassId = $studentData['class_id'] ?? $classId;
                    $studentStreamId = $studentData['stream_id'] ?? $streamId;

                    if ($studentClassId && $academicYearId) {
                        StudentEnrollment::create([
                            'school_id' => $schoolId,
                            'student_id' => $student->id,
                            'academic_year_id' => $academicYearId,
                            'class_id' => $studentClassId,
                            'stream_id' => $studentStreamId,
                            'status' => 'active',
                            'enrolled_at' => now(),
                        ]);
                    }

                    $created++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'row' => $index + 1,
                        'data' => $studentData,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'created' => $created,
                'failed' => $failed,
                'errors' => $errors,
            ];
        });

        return response()->json([
            'message' => "{$results['created']} students imported successfully, {$results['failed']} failed.",
            'created' => $results['created'],
            'failed' => $results['failed'],
            'errors' => $results['errors'],
        ]);
    }

    public function importMarks(Request $request): JsonResponse
    {
        $request->validate([
            'exam_subject_id' => ['required', 'uuid', 'exists:exam_subjects,id'],
            'file' => ['required_without:marks', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
            'marks' => ['required_without:file', 'array', 'min:1', 'max:500'],
            'marks.*.student_id' => ['required_without:marks.*.admission_number', 'uuid', 'exists:students,id'],
            'marks.*.admission_number' => ['required_without:marks.*.student_id', 'string'],
            'marks.*.score' => ['required_with:marks', 'numeric', 'min:0', 'max:100'],
            'marks.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $examSubject = ExamSubject::with('exam')->findOrFail($request->exam_subject_id);

        if ($examSubject->exam->status === 'locked') {
            return response()->json([
                'message' => 'Cannot import marks for a locked exam.',
            ], 422);
        }

        $schoolId = $request->user()->school_id;

        // Handle file upload (CSV/Excel)
        if ($request->hasFile('file')) {
            $import = new MarksImport($schoolId, $request->exam_subject_id);
            
            Excel::import($import, $request->file('file'));
            
            $results = $import->getResults();

            return response()->json([
                'message' => "{$results['created']} marks created, {$results['updated']} updated, {$results['failed']} failed.",
                'created' => $results['created'],
                'updated' => $results['updated'],
                'failed' => $results['failed'],
                'errors' => $results['errors'],
            ]);
        }

        // Handle JSON array (legacy support)
        $results = DB::transaction(function () use ($request, $examSubject, $schoolId) {
            $created = 0;
            $updated = 0;
            $failed = 0;
            $errors = [];

            foreach ($request->marks as $index => $markData) {
                try {
                    // Find student by ID or admission number
                    $studentId = $markData['student_id'] ?? null;
                    
                    if (!$studentId && !empty($markData['admission_number'])) {
                        $student = Student::where('school_id', $schoolId)
                            ->where('admission_number', $markData['admission_number'])
                            ->first();
                        $studentId = $student?->id;
                    }

                    if (!$studentId) {
                        $failed++;
                        $errors[] = [
                            'row' => $index + 1,
                            'error' => 'Student not found',
                            'data' => $markData,
                        ];
                        continue;
                    }

                    $existingMark = StudentMark::where('exam_subject_id', $examSubject->id)
                        ->where('student_id', $studentId)
                        ->first();

                    if ($existingMark) {
                        $existingMark->update([
                            'score' => $markData['score'],
                            'total_score' => $markData['score'],
                            'remarks' => $markData['remarks'] ?? $existingMark->remarks,
                        ]);
                        $updated++;
                    } else {
                        StudentMark::create([
                            'school_id' => $examSubject->exam->school_id,
                            'exam_subject_id' => $examSubject->id,
                            'student_id' => $studentId,
                            'score' => $markData['score'],
                            'total_score' => $markData['score'],
                            'remarks' => $markData['remarks'] ?? null,
                            'status' => StudentMark::STATUS_DRAFT,
                        ]);
                        $created++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'row' => $index + 1,
                        'data' => $markData,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'failed' => $failed,
                'errors' => $errors,
            ];
        });

        return response()->json([
            'message' => "{$results['created']} marks created, {$results['updated']} updated, {$results['failed']} failed.",
            'created' => $results['created'],
            'updated' => $results['updated'],
            'failed' => $results['failed'],
            'errors' => $results['errors'],
        ]);
    }

    public function importGuardians(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
        ]);

        $schoolId = $request->user()->school_id;

        $import = new GuardiansImport($schoolId);
        
        Excel::import($import, $request->file('file'));
        
        $results = $import->getResults();

        return response()->json([
            'message' => "{$results['created']} guardians created, {$results['updated']} updated, {$results['linked']} linked to students, {$results['failed']} failed.",
            'created' => $results['created'],
            'updated' => $results['updated'],
            'linked' => $results['linked'],
            'failed' => $results['failed'],
            'errors' => $results['errors'],
        ]);
    }

    public function downloadStudentTemplate(Request $request): BinaryFileResponse
    {
        $format = $request->input('format', 'xlsx');
        $filename = 'students_import_template.' . $format;

        return Excel::download(new StudentsTemplateExport(), $filename);
    }

    public function downloadMarksTemplate(Request $request): BinaryFileResponse
    {
        $format = $request->input('format', 'xlsx');
        $filename = 'marks_import_template.' . $format;

        return Excel::download(new MarksTemplateExport(), $filename);
    }

    public function downloadGuardiansTemplate(Request $request): BinaryFileResponse
    {
        $format = $request->input('format', 'xlsx');
        $filename = 'guardians_import_template.' . $format;

        return Excel::download(new GuardiansTemplateExport(), $filename);
    }

    public function getStudentTemplate(): JsonResponse
    {
        return response()->json([
            'message' => 'Use GET /api/v1/import/templates/students/download to download Excel/CSV template',
            'supported_formats' => ['xlsx', 'csv', 'xls'],
            'columns' => [
                'first_name' => 'Required - Student first name',
                'last_name' => 'Required - Student last name',
                'middle_name' => 'Optional - Middle name',
                'gender' => 'Required - male/female/M/F',
                'date_of_birth' => 'Optional - Format: YYYY-MM-DD',
                'admission_number' => 'Optional - Auto-generated if empty',
                'nationality' => 'Optional',
                'religion' => 'Optional',
                'address' => 'Optional',
                'medical_conditions' => 'Optional',
            ],
            'notes' => [
                'Upload CSV or Excel file to POST /api/v1/import/students',
                'Include academic_year_id and class_id to auto-enroll students',
                'Existing students (by admission_number) will be updated',
                'Maximum file size: 10MB',
            ],
        ]);
    }

    public function getMarksTemplate(): JsonResponse
    {
        return response()->json([
            'message' => 'Use GET /api/v1/import/templates/marks/download to download Excel/CSV template',
            'supported_formats' => ['xlsx', 'csv', 'xls'],
            'columns' => [
                'admission_number' => 'Required - Student admission number',
                'score' => 'Required - Score between 0-100',
                'remarks' => 'Optional - Comments',
            ],
            'notes' => [
                'Upload CSV or Excel file to POST /api/v1/import/marks',
                'Include exam_subject_id in the request',
                'Existing marks will be updated',
                'Maximum file size: 10MB',
            ],
        ]);
    }

    public function getGuardiansTemplate(): JsonResponse
    {
        return response()->json([
            'message' => 'Use GET /api/v1/import/templates/guardians/download to download Excel/CSV template',
            'supported_formats' => ['xlsx', 'csv', 'xls'],
            'columns' => [
                'first_name' => 'Required - Guardian first name',
                'last_name' => 'Required - Guardian last name',
                'phone' => 'Optional - Phone number',
                'email' => 'Optional - Email address',
                'relationship' => 'Required - father/mother/guardian/uncle/aunt/grandparent/sibling/other',
                'occupation' => 'Optional',
                'address' => 'Optional',
                'student_admission_number' => 'Optional - Link to student',
                'is_primary' => 'Optional - true/false',
                'is_emergency_contact' => 'Optional - true/false',
            ],
            'notes' => [
                'Upload CSV or Excel file to POST /api/v1/import/guardians',
                'Existing guardians (by email/phone) will be updated',
                'Maximum file size: 10MB',
            ],
        ]);
    }
}
