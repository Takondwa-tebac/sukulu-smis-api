<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\AttachGuardianRequest;
use App\Http\Requests\Student\EnrollStudentRequest;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Resources\Student\StudentResource;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @group Students
 *
 * APIs for managing students
 */
class StudentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Student::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->gender, fn ($q, $gender) => $q->where('gender', $gender))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('admission_number', 'like', "%{$search}%")
                        ->orWhere('student_id_number', 'like', "%{$search}%");
                });
            })
            ->when($request->class_id, function ($q, $classId) {
                $q->whereHas('currentEnrollment', fn ($e) => $e->where('class_id', $classId));
            })
            ->when($request->boolean('include_guardians'), fn ($q) => $q->with('guardians'))
            ->when($request->boolean('include_enrollment'), fn ($q) => $q->with(['currentEnrollment.schoolClass', 'currentEnrollment.stream']))
            ->orderBy('first_name')
            ->orderBy('last_name');

        return StudentResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $student = DB::transaction(function () use ($validated, $request) {
            $studentData = collect($validated)->except(['guardians', 'enrollment'])->toArray();
            $studentData['admission_number'] = Student::generateAdmissionNumber($request->user()->school);
            $studentData['admission_date'] = $studentData['admission_date'] ?? now();

            $student = Student::create($studentData);

            // Attach guardians
            if (!empty($validated['guardians'])) {
                foreach ($validated['guardians'] as $guardianData) {
                    $student->guardians()->attach($guardianData['guardian_id'], [
                        'id' => \Illuminate\Support\Str::uuid(),
                        'relationship' => $guardianData['relationship'],
                        'is_primary' => $guardianData['is_primary'] ?? false,
                    ]);
                }
            }

            // Create enrollment
            if (!empty($validated['enrollment'])) {
                StudentEnrollment::create([
                    'school_id' => $request->user()->school_id,
                    'student_id' => $student->id,
                    'academic_year_id' => $validated['enrollment']['academic_year_id'],
                    'class_id' => $validated['enrollment']['class_id'],
                    'stream_id' => $validated['enrollment']['stream_id'] ?? null,
                    'enrollment_date' => now(),
                    'status' => 'active',
                ]);
            }

            return $student;
        });

        return (new StudentResource($student->load(['guardians', 'currentEnrollment.schoolClass'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Student $student): StudentResource
    {
        return new StudentResource($student->load([
            'guardians',
            'currentEnrollment.schoolClass',
            'currentEnrollment.stream',
            'currentEnrollment.academicYear',
        ]));
    }

    public function update(UpdateStudentRequest $request, Student $student): StudentResource
    {
        $student->update($request->validated());

        return new StudentResource($student);
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json(['message' => 'Student deleted successfully.']);
    }

    public function attachGuardian(AttachGuardianRequest $request, Student $student): JsonResponse
    {
        $validated = $request->validated();

        if ($validated['is_primary'] ?? false) {
            $student->guardians()->updateExistingPivot(
                $student->guardians->pluck('id')->toArray(),
                ['is_primary' => false]
            );
        }

        $student->guardians()->attach($validated['guardian_id'], [
            'id' => \Illuminate\Support\Str::uuid(),
            'relationship' => $validated['relationship'],
            'is_primary' => $validated['is_primary'] ?? false,
            'is_emergency_contact' => $validated['is_emergency_contact'] ?? false,
        ]);

        return response()->json([
            'message' => 'Guardian attached successfully.',
            'data' => new StudentResource($student->load('guardians')),
        ]);
    }

    public function detachGuardian(Student $student, string $guardianId): JsonResponse
    {
        $student->guardians()->detach($guardianId);

        return response()->json(['message' => 'Guardian detached successfully.']);
    }

    public function enroll(EnrollStudentRequest $request, Student $student): JsonResponse
    {
        $validated = $request->validated();

        $existingEnrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'message' => 'Student is already enrolled for this academic year.',
            ], 422);
        }

        StudentEnrollment::create([
            'school_id' => $request->user()->school_id,
            'student_id' => $student->id,
            'academic_year_id' => $validated['academic_year_id'],
            'class_id' => $validated['class_id'],
            'stream_id' => $validated['stream_id'] ?? null,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Student enrolled successfully.',
            'data' => new StudentResource($student->load('currentEnrollment.schoolClass')),
        ], 201);
    }
}
