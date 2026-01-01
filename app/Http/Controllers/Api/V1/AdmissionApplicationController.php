<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdmissionApplicationResource;
use App\Models\AdmissionApplication;
use App\Models\AdmissionPeriod;
use App\Models\ApplicationComment;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * @group Admission Applications
 *
 * APIs for managing admission applications
 */
class AdmissionApplicationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AdmissionApplication::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->admission_period_id, fn ($q, $id) => $q->where('admission_period_id', $id))
            ->when($request->applied_class_id, fn ($q, $id) => $q->where('applied_class_id', $id))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('application_number', 'like', "%{$search}%");
                });
            })
            ->with(['admissionPeriod', 'appliedClass'])
            ->orderByDesc('created_at');

        return AdmissionApplicationResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'admission_period_id' => ['required', 'uuid', 'exists:admission_periods,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'birth_certificate_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'previous_school_address' => ['nullable', 'string', 'max:500'],
            'previous_class' => ['nullable', 'string', 'max:50'],
            'previous_average' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'applied_class_id' => ['required', 'uuid', 'exists:classes,id'],
            'preferred_stream_id' => ['nullable', 'uuid', 'exists:streams,id'],
            'guardian_first_name' => ['required', 'string', 'max:100'],
            'guardian_last_name' => ['required', 'string', 'max:100'],
            'guardian_relationship' => ['required', 'string', 'max:50'],
            'guardian_phone' => ['required', 'string', 'max:20'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_occupation' => ['nullable', 'string', 'max:100'],
            'guardian_address' => ['nullable', 'string', 'max:500'],
        ]);

        $period = AdmissionPeriod::findOrFail($validated['admission_period_id']);

        if (!$period->canAcceptApplications()) {
            return response()->json([
                'message' => 'This admission period is not accepting applications.',
            ], 422);
        }

        $application = AdmissionApplication::create($validated);

        return (new AdmissionApplicationResource($application->load(['admissionPeriod', 'appliedClass'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AdmissionApplication $admissionApplication): AdmissionApplicationResource
    {
        return new AdmissionApplicationResource(
            $admissionApplication->load([
                'admissionPeriod',
                'appliedClass',
                'preferredStream',
                'documents',
                'statusHistory.changedByUser',
                'comments.createdByUser',
            ])
        );
    }

    public function update(Request $request, AdmissionApplication $admissionApplication): JsonResponse
    {
        if (!$admissionApplication->canBeEdited()) {
            return response()->json([
                'message' => 'This application cannot be edited in its current status.',
            ], 422);
        }

        $validated = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'gender' => ['sometimes', 'required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['sometimes', 'required', 'date', 'before:today'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'birth_certificate_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'previous_school_address' => ['nullable', 'string', 'max:500'],
            'previous_class' => ['nullable', 'string', 'max:50'],
            'previous_average' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'applied_class_id' => ['sometimes', 'required', 'uuid', 'exists:classes,id'],
            'preferred_stream_id' => ['nullable', 'uuid', 'exists:streams,id'],
            'guardian_first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'guardian_last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'guardian_relationship' => ['sometimes', 'required', 'string', 'max:50'],
            'guardian_phone' => ['sometimes', 'required', 'string', 'max:20'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_occupation' => ['nullable', 'string', 'max:100'],
            'guardian_address' => ['nullable', 'string', 'max:500'],
        ]);

        $admissionApplication->update($validated);

        return response()->json([
            'message' => 'Application updated successfully.',
            'data' => new AdmissionApplicationResource($admissionApplication),
        ]);
    }

    public function destroy(AdmissionApplication $admissionApplication): JsonResponse
    {
        if (!in_array($admissionApplication->status, [AdmissionApplication::STATUS_DRAFT, AdmissionApplication::STATUS_WITHDRAWN])) {
            return response()->json([
                'message' => 'Only draft or withdrawn applications can be deleted.',
            ], 422);
        }

        $admissionApplication->delete();

        return response()->json(['message' => 'Application deleted successfully.']);
    }

    public function submit(AdmissionApplication $admissionApplication): JsonResponse
    {
        if (!$admissionApplication->canBeSubmitted()) {
            return response()->json([
                'message' => 'This application cannot be submitted.',
            ], 422);
        }

        $admissionApplication->submit();

        return response()->json([
            'message' => 'Application submitted successfully.',
            'data' => new AdmissionApplicationResource($admissionApplication),
        ]);
    }

    public function updateStatus(Request $request, AdmissionApplication $admissionApplication): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(AdmissionApplication::getStatuses())],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $admissionApplication->updateStatus(
            $validated['status'],
            $validated['reason'] ?? null,
            $request->user()->id
        );

        return response()->json([
            'message' => 'Application status updated successfully.',
            'data' => new AdmissionApplicationResource($admissionApplication->load('statusHistory')),
        ]);
    }

    public function approve(Request $request, AdmissionApplication $admissionApplication): JsonResponse
    {
        if ($admissionApplication->status === AdmissionApplication::STATUS_APPROVED) {
            return response()->json(['message' => 'Application is already approved.'], 422);
        }

        $admissionApplication->approve($request->user()->id);

        return response()->json([
            'message' => 'Application approved successfully.',
            'data' => new AdmissionApplicationResource($admissionApplication),
        ]);
    }

    public function reject(Request $request, AdmissionApplication $admissionApplication): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $admissionApplication->reject($validated['reason'], $request->user()->id);

        return response()->json([
            'message' => 'Application rejected.',
            'data' => new AdmissionApplicationResource($admissionApplication),
        ]);
    }

    public function enroll(Request $request, AdmissionApplication $admissionApplication): JsonResponse
    {
        if ($admissionApplication->status !== AdmissionApplication::STATUS_APPROVED) {
            return response()->json([
                'message' => 'Only approved applications can be enrolled.',
            ], 422);
        }

        $validated = $request->validate([
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'stream_id' => ['nullable', 'uuid', 'exists:streams,id'],
        ]);

        $student = DB::transaction(function () use ($admissionApplication, $validated, $request) {
            // Create student
            $student = Student::create([
                'school_id' => $admissionApplication->school_id,
                'first_name' => $admissionApplication->first_name,
                'middle_name' => $admissionApplication->middle_name,
                'last_name' => $admissionApplication->last_name,
                'gender' => $admissionApplication->gender,
                'date_of_birth' => $admissionApplication->date_of_birth,
                'place_of_birth' => $admissionApplication->place_of_birth,
                'nationality' => $admissionApplication->nationality,
                'birth_certificate_number' => $admissionApplication->birth_certificate_number,
                'address' => $admissionApplication->address,
                'city' => $admissionApplication->city,
                'region' => $admissionApplication->region,
                'country' => $admissionApplication->country,
                'previous_school' => $admissionApplication->previous_school,
                'previous_school_address' => $admissionApplication->previous_school_address,
                'admission_date' => now(),
                'status' => Student::STATUS_ACTIVE,
            ]);

            // Create guardian
            $guardian = Guardian::create([
                'school_id' => $admissionApplication->school_id,
                'first_name' => $admissionApplication->guardian_first_name,
                'last_name' => $admissionApplication->guardian_last_name,
                'phone_primary' => $admissionApplication->guardian_phone,
                'email' => $admissionApplication->guardian_email,
                'occupation' => $admissionApplication->guardian_occupation,
                'address' => $admissionApplication->guardian_address,
                'relationship_type' => $admissionApplication->guardian_relationship,
            ]);

            // Link guardian to student
            $student->guardians()->attach($guardian->id, [
                'id' => \Illuminate\Support\Str::uuid(),
                'relationship' => $admissionApplication->guardian_relationship,
                'is_primary' => true,
            ]);

            // Create enrollment
            StudentEnrollment::create([
                'school_id' => $admissionApplication->school_id,
                'student_id' => $student->id,
                'academic_year_id' => $validated['academic_year_id'],
                'class_id' => $admissionApplication->applied_class_id,
                'stream_id' => $validated['stream_id'] ?? $admissionApplication->preferred_stream_id,
                'enrollment_date' => now(),
                'status' => StudentEnrollment::STATUS_ACTIVE,
            ]);

            // Update application status
            $admissionApplication->updateStatus(
                AdmissionApplication::STATUS_ENROLLED,
                'Student enrolled successfully',
                $request->user()->id
            );

            return $student;
        });

        return response()->json([
            'message' => 'Student enrolled successfully.',
            'student_id' => $student->id,
            'admission_number' => $student->admission_number,
        ]);
    }

    public function addComment(Request $request, AdmissionApplication $admissionApplication): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
            'is_internal' => ['boolean'],
        ]);

        $comment = ApplicationComment::create([
            'admission_application_id' => $admissionApplication->id,
            'comment' => $validated['comment'],
            'is_internal' => $validated['is_internal'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Comment added successfully.',
            'data' => $comment->load('createdByUser'),
        ], 201);
    }

    public function scheduleInterview(Request $request, AdmissionApplication $admissionApplication): JsonResponse
    {
        $validated = $request->validate([
            'interview_date' => ['required', 'date', 'after:now'],
        ]);

        $admissionApplication->update([
            'interview_date' => $validated['interview_date'],
        ]);

        $admissionApplication->updateStatus(
            AdmissionApplication::STATUS_INTERVIEW_SCHEDULED,
            'Interview scheduled for ' . $validated['interview_date'],
            $request->user()->id
        );

        return response()->json([
            'message' => 'Interview scheduled successfully.',
            'data' => new AdmissionApplicationResource($admissionApplication),
        ]);
    }

    public function recordInterview(Request $request, AdmissionApplication $admissionApplication): JsonResponse
    {
        $validated = $request->validate([
            'interview_notes' => ['required', 'string', 'max:5000'],
            'interview_score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $admissionApplication->update($validated);

        $admissionApplication->updateStatus(
            AdmissionApplication::STATUS_INTERVIEWED,
            'Interview completed',
            $request->user()->id
        );

        return response()->json([
            'message' => 'Interview recorded successfully.',
            'data' => new AdmissionApplicationResource($admissionApplication),
        ]);
    }
}
