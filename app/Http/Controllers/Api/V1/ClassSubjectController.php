<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @group Academic Structure - Class Subjects
 *
 * APIs for assigning subjects to classes and managing subject-teacher assignments
 */
class ClassSubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:subjects.view')->only(['index', 'show', 'byClass']);
        $this->middleware('permission:subjects.manage')->only(['store', 'update', 'destroy', 'bulkAssign', 'assignTeacher']);
    }

    /**
     * List class-subject assignments
     *
     * Get a paginated list of subject assignments to classes.
     *
     * @authenticated
     * @queryParam class_id uuid Filter by class ID. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @queryParam subject_id uuid Filter by subject ID. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @queryParam is_compulsory boolean Filter by compulsory status. Example: true
     * @queryParam per_page integer Items per page. Default: 15. Example: 20
     *
     * @response 200 scenario="Success" {"data": [], "links": {}, "meta": {}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ClassSubject::query()
            ->when($request->class_id, fn ($q, $id) => $q->where('class_id', $id))
            ->when($request->subject_id, fn ($q, $id) => $q->where('subject_id', $id))
            ->when($request->is_compulsory !== null, fn ($q) => $q->where('is_compulsory', $request->boolean('is_compulsory')))
            ->with(['schoolClass', 'subject', 'teacher'])
            ->orderBy('created_at');

        return JsonResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'uuid', 'exists:classes,id'],
            'subject_id' => ['required', 'uuid', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'uuid', 'exists:users,id'],
            'is_compulsory' => ['boolean'],
            'periods_per_week' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $exists = ClassSubject::where('class_id', $validated['class_id'])
            ->where('subject_id', $validated['subject_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This subject is already assigned to this class.',
            ], 422);
        }

        $classSubject = ClassSubject::create([
            'school_id' => $request->user()->school_id,
            'class_id' => $validated['class_id'],
            'subject_id' => $validated['subject_id'],
            'teacher_id' => $validated['teacher_id'] ?? null,
            'is_compulsory' => $validated['is_compulsory'] ?? true,
            'periods_per_week' => $validated['periods_per_week'] ?? null,
        ]);

        return response()->json([
            'message' => 'Subject assigned to class successfully.',
            'data' => new JsonResource($classSubject->load(['schoolClass', 'subject', 'teacher'])),
        ], 201);
    }

    public function show(ClassSubject $classSubject): JsonResource
    {
        return new JsonResource($classSubject->load(['schoolClass', 'subject', 'teacher']));
    }

    public function update(Request $request, ClassSubject $classSubject): JsonResource
    {
        $validated = $request->validate([
            'teacher_id' => ['nullable', 'uuid', 'exists:users,id'],
            'is_compulsory' => ['boolean'],
            'periods_per_week' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $classSubject->update($validated);

        return new JsonResource($classSubject->load(['schoolClass', 'subject', 'teacher']));
    }

    public function destroy(ClassSubject $classSubject): JsonResponse
    {
        if ($classSubject->examSubjects()->exists()) {
            return response()->json([
                'message' => 'Cannot remove subject with existing exam records.',
            ], 422);
        }

        $classSubject->delete();

        return response()->json(['message' => 'Subject removed from class successfully.']);
    }

    public function bulkAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'uuid', 'exists:classes,id'],
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['uuid', 'exists:subjects,id'],
        ]);

        $schoolId = $request->user()->school_id;
        $classId = $validated['class_id'];
        $created = 0;
        $skipped = 0;

        foreach ($validated['subject_ids'] as $subjectId) {
            $exists = ClassSubject::where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            ClassSubject::create([
                'school_id' => $schoolId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'is_compulsory' => true,
            ]);
            $created++;
        }

        return response()->json([
            'message' => "{$created} subjects assigned, {$skipped} skipped (already assigned).",
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    public function byClass(SchoolClass $class): AnonymousResourceCollection
    {
        $classSubjects = ClassSubject::where('class_id', $class->id)
            ->with(['subject', 'teacher'])
            ->get();

        return JsonResource::collection($classSubjects);
    }

    public function assignTeacher(Request $request, ClassSubject $classSubject): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $classSubject->update(['teacher_id' => $validated['teacher_id']]);

        return response()->json([
            'message' => 'Teacher assigned successfully.',
            'data' => new JsonResource($classSubject->load(['subject', 'teacher'])),
        ]);
    }
}
