<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradingSystem\CalculateGradeRequest;
use App\Http\Requests\GradingSystem\StoreGradingSystemRequest;
use App\Http\Requests\GradingSystem\UpdateGradingSystemRequest;
use App\Http\Resources\GradingSystemResource;
use App\Models\GradeScale;
use App\Models\GradingSystem;
use App\Services\Grading\GradingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @group Grading Systems
 *
 * APIs for managing grading systems and calculating grades
 */
class GradingSystemController extends Controller
{
    /**
     * List all grading systems
     *
     * Get a paginated list of grading systems available to the authenticated user's school.
     * System defaults are always included.
     *
     * @queryParam per_page int Number of items per page. Default: 15
     * @queryParam type string Filter by type (primary, secondary_jce, secondary_msce, international)
     * @queryParam is_active boolean Filter by active status
     * @queryParam include_scales boolean Include grade scales in response. Default: false
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = GradingSystem::query()
            ->where(function ($q) use ($request) {
                $q->whereNull('school_id')
                  ->where('is_system_default', true);

                if ($request->user()->school_id) {
                    $q->orWhere('school_id', $request->user()->school_id);
                }
            })
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('is_system_default', 'desc')
            ->orderBy('name');

        if ($request->boolean('include_scales')) {
            $query->with('gradeScales');
        }

        $gradingSystems = $query->paginate($request->input('per_page', 15));

        return GradingSystemResource::collection($gradingSystems);
    }

    /**
     * Get a grading system
     *
     * Retrieve details of a specific grading system including its grade scales.
     *
     * @urlParam grading_system uuid required The grading system ID
     */
    public function show(GradingSystem $gradingSystem): GradingSystemResource
    {
        $gradingSystem->load('gradeScales');

        return new GradingSystemResource($gradingSystem);
    }

    /**
     * Create a grading system
     *
     * Create a new custom grading system for the school.
     *
     * @bodyParam name string required The name of the grading system. Example: Custom Primary Grading
     * @bodyParam code string required Unique code for the grading system. Example: custom-primary
     * @bodyParam type string required Type of grading system. Example: primary
     * @bodyParam scale_type string required Scale type (letter, numeric, percentage, gpa, points). Example: percentage
     * @bodyParam min_score number required Minimum possible score. Example: 0
     * @bodyParam max_score number required Maximum possible score. Example: 100
     * @bodyParam pass_mark number required Passing mark. Example: 50
     * @bodyParam min_subjects_to_pass int required Minimum subjects to pass. Example: 6
     * @bodyParam grade_scales array required Array of grade scale definitions
     */
    public function store(StoreGradingSystemRequest $request): JsonResponse
    {
        $gradingSystem = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $gradeScales = $data['grade_scales'];
            unset($data['grade_scales']);

            $data['school_id'] = $request->user()->school_id;

            $gradingSystem = GradingSystem::create($data);

            foreach ($gradeScales as $index => $scale) {
                $scale['sort_order'] = $scale['sort_order'] ?? $index;
                $gradingSystem->gradeScales()->create($scale);
            }

            return $gradingSystem->load('gradeScales');
        });

        return (new GradingSystemResource($gradingSystem))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a grading system
     *
     * Update an existing grading system. Locked system defaults can only be updated by super admins.
     *
     * @urlParam grading_system uuid required The grading system ID
     */
    public function update(UpdateGradingSystemRequest $request, GradingSystem $gradingSystem): GradingSystemResource
    {
        $gradingSystem->update($request->validated());
        $gradingSystem->incrementVersion();
        $gradingSystem->load('gradeScales');

        return new GradingSystemResource($gradingSystem);
    }

    /**
     * Delete a grading system
     *
     * Delete a custom grading system. System defaults cannot be deleted.
     *
     * @urlParam grading_system uuid required The grading system ID
     */
    public function destroy(GradingSystem $gradingSystem): JsonResponse
    {
        if ($gradingSystem->is_system_default) {
            return response()->json([
                'message' => 'System default grading systems cannot be deleted.',
            ], 403);
        }

        if ($gradingSystem->is_locked && !request()->user()->isSuperAdmin()) {
            return response()->json([
                'message' => 'This grading system is locked and cannot be deleted.',
            ], 403);
        }

        $gradingSystem->delete();

        return response()->json([
            'message' => 'Grading system deleted successfully.',
        ]);
    }

    /**
     * Calculate grades
     *
     * Calculate grades for a set of subject scores using a specific grading system.
     *
     * @bodyParam grading_system_id uuid required The grading system to use
     * @bodyParam scores array required Array of subject scores
     * @bodyParam scores.*.subject_code string required Subject code. Example: ENG
     * @bodyParam scores.*.score number required Score achieved. Example: 75
     */
    public function calculate(CalculateGradeRequest $request): JsonResponse
    {
        $gradingSystem = GradingSystem::with('gradeScales')
            ->findOrFail($request->grading_system_id);

        $engine = new GradingEngine($gradingSystem);

        $subjectScores = collect($request->scores)
            ->pluck('score', 'subject_code')
            ->toArray();

        $results = $engine->calculateOverallResult($subjectScores);

        return response()->json([
            'grading_system' => [
                'id' => $gradingSystem->id,
                'name' => $gradingSystem->name,
                'code' => $gradingSystem->code,
            ],
            'results' => $results,
        ]);
    }

    /**
     * Get system defaults
     *
     * Get all system default grading systems (Malawi and International).
     */
    public function systemDefaults(): AnonymousResourceCollection
    {
        $gradingSystems = GradingSystem::systemDefaults()
            ->active()
            ->with('gradeScales')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return GradingSystemResource::collection($gradingSystems);
    }

    /**
     * Update grade scales
     *
     * Update the grade scales for a grading system.
     *
     * @urlParam grading_system uuid required The grading system ID
     * @bodyParam grade_scales array required Array of grade scale definitions
     */
    public function updateGradeScales(Request $request, GradingSystem $gradingSystem): JsonResponse
    {
        if ($gradingSystem->is_locked && !$request->user()->isSuperAdmin()) {
            return response()->json([
                'message' => 'This grading system is locked and cannot be modified.',
            ], 403);
        }

        $request->validate([
            'grade_scales' => ['required', 'array', 'min:1'],
            'grade_scales.*.id' => ['nullable', 'uuid'],
            'grade_scales.*.grade' => ['required', 'string', 'max:10'],
            'grade_scales.*.grade_label' => ['nullable', 'string', 'max:50'],
            'grade_scales.*.min_score' => ['required', 'numeric'],
            'grade_scales.*.max_score' => ['required', 'numeric'],
            'grade_scales.*.gpa_points' => ['nullable', 'numeric', 'min:0'],
            'grade_scales.*.points' => ['nullable', 'integer'],
            'grade_scales.*.remark' => ['nullable', 'string', 'max:100'],
            'grade_scales.*.is_passing' => ['required', 'boolean'],
            'grade_scales.*.sort_order' => ['nullable', 'integer'],
        ]);

        DB::transaction(function () use ($request, $gradingSystem) {
            $gradingSystem->createHistorySnapshot('Grade scales updated');

            $existingIds = collect($request->grade_scales)
                ->pluck('id')
                ->filter()
                ->toArray();

            $gradingSystem->gradeScales()
                ->whereNotIn('id', $existingIds)
                ->delete();

            foreach ($request->grade_scales as $index => $scaleData) {
                $scaleData['sort_order'] = $scaleData['sort_order'] ?? $index;

                if (!empty($scaleData['id'])) {
                    GradeScale::where('id', $scaleData['id'])
                        ->where('grading_system_id', $gradingSystem->id)
                        ->update($scaleData);
                } else {
                    $gradingSystem->gradeScales()->create($scaleData);
                }
            }

            $gradingSystem->incrementVersion();
        });

        $gradingSystem->load('gradeScales');

        return response()->json([
            'message' => 'Grade scales updated successfully.',
            'data' => new GradingSystemResource($gradingSystem),
        ]);
    }
}
