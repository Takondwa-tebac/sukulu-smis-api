<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\StudentEnrollment;
use App\Models\StudentPromotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

/**
 * @group Students - Promotions
 *
 * APIs for promoting students to next class at end of academic year
 */
class StudentPromotionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:students.view')->only(['index']);
        $this->middleware('permission:students.promote')->only(['preview', 'promote', 'repeat']);
    }

    /**
     * List promotion history
     *
     * @authenticated
     * @queryParam academic_year_id uuid Filter by source academic year. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @queryParam from_class_id uuid Filter by source class. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @queryParam status string Filter by status (promoted, repeated). Example: promoted
     *
     * @response 200 scenario="Success" {"data": [], "links": {}, "meta": {}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = StudentPromotion::query()
            ->when($request->academic_year_id, fn ($q, $id) => $q->where('from_academic_year_id', $id))
            ->when($request->from_class_id, fn ($q, $id) => $q->where('from_class_id', $id))
            ->when($request->to_class_id, fn ($q, $id) => $q->where('to_class_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->with(['student', 'fromClass', 'toClass', 'fromAcademicYear', 'toAcademicYear'])
            ->orderByDesc('promoted_at');

        return JsonResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'from_class_id' => ['required', 'uuid', 'exists:classes,id'],
            'to_academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'to_class_id' => ['required', 'uuid', 'exists:classes,id'],
            'min_average' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $enrollments = StudentEnrollment::where('academic_year_id', $validated['from_academic_year_id'])
            ->where('class_id', $validated['from_class_id'])
            ->where('status', 'active')
            ->with(['student', 'reportCards' => fn ($q) => $q->where('academic_year_id', $validated['from_academic_year_id'])])
            ->get();

        $eligible = [];
        $notEligible = [];
        $minAverage = $validated['min_average'] ?? 0;

        foreach ($enrollments as $enrollment) {
            $avgScore = $enrollment->reportCards->avg('average_score') ?? 0;
            
            $studentData = [
                'student_id' => $enrollment->student_id,
                'student_name' => $enrollment->student->full_name,
                'admission_number' => $enrollment->student->admission_number,
                'average_score' => round($avgScore, 2),
                'stream_id' => $enrollment->stream_id,
            ];

            if ($avgScore >= $minAverage) {
                $eligible[] = $studentData;
            } else {
                $notEligible[] = $studentData;
            }
        }

        return response()->json([
            'eligible_count' => count($eligible),
            'not_eligible_count' => count($notEligible),
            'eligible' => $eligible,
            'not_eligible' => $notEligible,
        ]);
    }

    public function promote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'from_class_id' => ['required', 'uuid', 'exists:classes,id'],
            'to_academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'to_class_id' => ['required', 'uuid', 'exists:classes,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['uuid', 'exists:students,id'],
            'to_stream_id' => ['nullable', 'uuid', 'exists:streams,id'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $result = DB::transaction(function () use ($validated, $request) {
            $promoted = 0;
            $failed = 0;
            $userId = $request->user()->id;
            $schoolId = $request->user()->school_id;

            foreach ($validated['student_ids'] as $studentId) {
                // Check if already promoted
                $alreadyPromoted = StudentPromotion::where('student_id', $studentId)
                    ->where('from_academic_year_id', $validated['from_academic_year_id'])
                    ->where('from_class_id', $validated['from_class_id'])
                    ->exists();

                if ($alreadyPromoted) {
                    $failed++;
                    continue;
                }

                // Get current enrollment
                $currentEnrollment = StudentEnrollment::where('student_id', $studentId)
                    ->where('academic_year_id', $validated['from_academic_year_id'])
                    ->where('class_id', $validated['from_class_id'])
                    ->first();

                if (!$currentEnrollment) {
                    $failed++;
                    continue;
                }

                // Create promotion record
                StudentPromotion::create([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'from_academic_year_id' => $validated['from_academic_year_id'],
                    'to_academic_year_id' => $validated['to_academic_year_id'],
                    'from_class_id' => $validated['from_class_id'],
                    'to_class_id' => $validated['to_class_id'],
                    'from_stream_id' => $currentEnrollment->stream_id,
                    'to_stream_id' => $validated['to_stream_id'] ?? $currentEnrollment->stream_id,
                    'status' => 'promoted',
                    'remarks' => $validated['remarks'] ?? null,
                    'promoted_at' => now(),
                    'promoted_by' => $userId,
                ]);

                // Update old enrollment
                $currentEnrollment->update(['status' => 'promoted']);

                // Create new enrollment
                StudentEnrollment::create([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'academic_year_id' => $validated['to_academic_year_id'],
                    'class_id' => $validated['to_class_id'],
                    'stream_id' => $validated['to_stream_id'] ?? $currentEnrollment->stream_id,
                    'status' => 'active',
                    'enrolled_at' => now(),
                ]);

                $promoted++;
            }

            return ['promoted' => $promoted, 'failed' => $failed];
        });

        return response()->json([
            'message' => "{$result['promoted']} students promoted successfully, {$result['failed']} failed.",
            'promoted' => $result['promoted'],
            'failed' => $result['failed'],
        ]);
    }

    public function repeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'to_academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'class_id' => ['required', 'uuid', 'exists:classes,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['uuid', 'exists:students,id'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $result = DB::transaction(function () use ($validated, $request) {
            $repeated = 0;
            $userId = $request->user()->id;
            $schoolId = $request->user()->school_id;

            foreach ($validated['student_ids'] as $studentId) {
                $currentEnrollment = StudentEnrollment::where('student_id', $studentId)
                    ->where('academic_year_id', $validated['from_academic_year_id'])
                    ->where('class_id', $validated['class_id'])
                    ->first();

                if (!$currentEnrollment) {
                    continue;
                }

                StudentPromotion::create([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'from_academic_year_id' => $validated['from_academic_year_id'],
                    'to_academic_year_id' => $validated['to_academic_year_id'],
                    'from_class_id' => $validated['class_id'],
                    'to_class_id' => $validated['class_id'],
                    'from_stream_id' => $currentEnrollment->stream_id,
                    'to_stream_id' => $currentEnrollment->stream_id,
                    'status' => 'repeated',
                    'remarks' => $validated['remarks'] ?? 'Repeating class',
                    'promoted_at' => now(),
                    'promoted_by' => $userId,
                ]);

                $currentEnrollment->update(['status' => 'repeated']);

                StudentEnrollment::create([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'academic_year_id' => $validated['to_academic_year_id'],
                    'class_id' => $validated['class_id'],
                    'stream_id' => $currentEnrollment->stream_id,
                    'status' => 'active',
                    'enrolled_at' => now(),
                ]);

                $repeated++;
            }

            return $repeated;
        });

        return response()->json([
            'message' => "{$result} students set to repeat class.",
            'repeated' => $result,
        ]);
    }
}
