<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DisciplineAction;
use App\Models\DisciplineCategory;
use App\Models\DisciplineIncident;
use App\Models\DisciplineIncidentAction;
use App\Models\DisciplineNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

/**
 * @group Discipline
 *
 * APIs for managing student discipline records, incidents, and actions
 */
class DisciplineController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:discipline.view')->only([
            'categories', 'showCategory', 'actions', 'showAction',
            'incidents', 'showIncident', 'studentHistory'
        ]);
        $this->middleware('permission:discipline.create')->only([
            'storeCategory', 'storeAction', 'storeIncident', 'addIncidentAction', 'notifyGuardian'
        ]);
        $this->middleware('permission:discipline.manage')->only([
            'updateCategory', 'destroyCategory', 'updateAction', 'destroyAction',
            'updateIncident', 'resolveIncident', 'approveAction', 'completeAction'
        ]);
    }

    // ==================== CATEGORIES ====================

    /**
     * List discipline categories
     *
     * @authenticated
     * @queryParam severity string Filter by severity (minor, moderate, major, critical). Example: major
     * @queryParam is_active boolean Filter by active status. Example: true
     *
     * @response 200 scenario="Success" {"data": []}
     */
    public function categories(Request $request): AnonymousResourceCollection
    {
        $query = DisciplineCategory::query()
            ->when($request->severity, fn ($q, $s) => $q->where('severity', $s))
            ->when($request->is_active !== null, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('severity')
            ->orderBy('name');

        return JsonResource::collection($query->paginate($request->input('per_page', 15)));
    }

    /**
     * Create discipline category
     *
     * @authenticated
     * @bodyParam name string required Category name. Example: Fighting
     * @bodyParam severity string required Severity level. Example: major
     * @bodyParam description string Description. Example: Physical altercation between students
     * @bodyParam default_points integer Default points. Example: 10
     *
     * @response 201 scenario="Created" {"message": "Category created.", "data": {}}
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'severity' => ['required', 'in:minor,moderate,major,critical'],
            'default_points' => ['integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $category = DisciplineCategory::create([
            'school_id' => $request->user()->school_id,
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Discipline category created successfully.',
            'data' => $category,
        ], 201);
    }

    /**
     * Get discipline category
     *
     * @authenticated
     * @urlParam category uuid required The category ID.
     */
    public function showCategory(DisciplineCategory $category): JsonResource
    {
        return new JsonResource($category);
    }

    /**
     * Update discipline category
     *
     * @authenticated
     */
    public function updateCategory(Request $request, DisciplineCategory $category): JsonResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'severity' => ['sometimes', 'in:minor,moderate,major,critical'],
            'default_points' => ['integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $category->update($validated);

        return new JsonResource($category);
    }

    /**
     * Delete discipline category
     *
     * @authenticated
     */
    public function destroyCategory(DisciplineCategory $category): JsonResponse
    {
        if ($category->incidents()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with existing incidents.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully.']);
    }

    // ==================== ACTIONS ====================

    /**
     * List discipline actions
     *
     * @authenticated
     * @queryParam type string Filter by type. Example: suspension
     * @queryParam is_active boolean Filter by active status. Example: true
     */
    public function actions(Request $request): AnonymousResourceCollection
    {
        $query = DisciplineAction::query()
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->is_active !== null, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('type')
            ->orderBy('name');

        return JsonResource::collection($query->paginate($request->input('per_page', 15)));
    }

    /**
     * Create discipline action
     *
     * @authenticated
     */
    public function storeAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'in:warning,detention,suspension,expulsion,community_service,counseling,other'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'requires_parent_notification' => ['boolean'],
            'requires_approval' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $action = DisciplineAction::create([
            'school_id' => $request->user()->school_id,
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Discipline action created successfully.',
            'data' => $action,
        ], 201);
    }

    /**
     * Get discipline action
     *
     * @authenticated
     */
    public function showAction(DisciplineAction $action): JsonResource
    {
        return new JsonResource($action);
    }

    /**
     * Update discipline action
     *
     * @authenticated
     */
    public function updateAction(Request $request, DisciplineAction $action): JsonResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['sometimes', 'in:warning,detention,suspension,expulsion,community_service,counseling,other'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'requires_parent_notification' => ['boolean'],
            'requires_approval' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $action->update($validated);

        return new JsonResource($action);
    }

    /**
     * Delete discipline action
     *
     * @authenticated
     */
    public function destroyAction(DisciplineAction $action): JsonResponse
    {
        if ($action->incidentActions()->exists()) {
            return response()->json([
                'message' => 'Cannot delete action that has been used in incidents.',
            ], 422);
        }

        $action->delete();

        return response()->json(['message' => 'Action deleted successfully.']);
    }

    // ==================== INCIDENTS ====================

    /**
     * List discipline incidents
     *
     * @authenticated
     * @queryParam student_id uuid Filter by student.
     * @queryParam category_id uuid Filter by category.
     * @queryParam status string Filter by status.
     * @queryParam academic_year_id uuid Filter by academic year.
     * @queryParam from_date date Filter from date.
     * @queryParam to_date date Filter to date.
     */
    public function incidents(Request $request): AnonymousResourceCollection
    {
        $query = DisciplineIncident::query()
            ->when($request->student_id, fn ($q, $id) => $q->where('student_id', $id))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->academic_year_id, fn ($q, $id) => $q->where('academic_year_id', $id))
            ->when($request->from_date, fn ($q, $d) => $q->where('incident_date', '>=', $d))
            ->when($request->to_date, fn ($q, $d) => $q->where('incident_date', '<=', $d))
            ->with(['student', 'category', 'reportedBy', 'actions.action'])
            ->orderByDesc('incident_date');

        return JsonResource::collection($query->paginate($request->input('per_page', 15)));
    }

    /**
     * Report discipline incident
     *
     * @authenticated
     * @bodyParam student_id uuid required The student ID.
     * @bodyParam category_id uuid required The category ID.
     * @bodyParam incident_date date required Date of incident.
     * @bodyParam description string required Description of incident.
     */
    public function storeIncident(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'category_id' => ['required', 'uuid', 'exists:discipline_categories,id'],
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'term_id' => ['nullable', 'uuid', 'exists:terms,id'],
            'incident_date' => ['required', 'date'],
            'incident_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:2000'],
            'witnesses' => ['nullable', 'string', 'max:500'],
            'points_assigned' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $category = DisciplineCategory::find($validated['category_id']);

        $incident = DisciplineIncident::create([
            'school_id' => $request->user()->school_id,
            'reported_by' => $request->user()->id,
            'points_assigned' => $validated['points_assigned'] ?? $category->default_points,
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Discipline incident reported successfully.',
            'data' => $incident->load(['student', 'category', 'reportedBy']),
        ], 201);
    }

    /**
     * Get discipline incident
     *
     * @authenticated
     */
    public function showIncident(DisciplineIncident $incident): JsonResource
    {
        return new JsonResource($incident->load([
            'student', 'category', 'academicYear', 'term',
            'reportedBy', 'resolvedBy', 'actions.action', 'notifications.guardian'
        ]));
    }

    /**
     * Update discipline incident
     *
     * @authenticated
     */
    public function updateIncident(Request $request, DisciplineIncident $incident): JsonResource
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'uuid', 'exists:discipline_categories,id'],
            'incident_date' => ['sometimes', 'date'],
            'incident_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:200'],
            'description' => ['sometimes', 'string', 'max:2000'],
            'witnesses' => ['nullable', 'string', 'max:500'],
            'points_assigned' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['sometimes', 'in:reported,under_investigation,resolved,dismissed,appealed'],
        ]);

        $incident->update($validated);

        return new JsonResource($incident->load(['student', 'category']));
    }

    /**
     * Resolve discipline incident
     *
     * @authenticated
     * @bodyParam resolution_notes string required Notes about resolution.
     */
    public function resolveIncident(Request $request, DisciplineIncident $incident): JsonResponse
    {
        $validated = $request->validate([
            'resolution_notes' => ['required', 'string', 'max:2000'],
            'status' => ['sometimes', 'in:resolved,dismissed'],
        ]);

        $incident->update([
            'status' => $validated['status'] ?? DisciplineIncident::STATUS_RESOLVED,
            'resolution_notes' => $validated['resolution_notes'],
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Incident resolved successfully.',
            'data' => $incident->load(['student', 'category', 'resolvedBy']),
        ]);
    }

    /**
     * Add action to incident
     *
     * @authenticated
     */
    public function addIncidentAction(Request $request, DisciplineIncident $incident): JsonResponse
    {
        $validated = $request->validate([
            'action_id' => ['required', 'uuid', 'exists:discipline_actions,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $action = DisciplineAction::find($validated['action_id']);

        $incidentAction = DisciplineIncidentAction::create([
            'incident_id' => $incident->id,
            'action_id' => $validated['action_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? ($action->duration_days 
                ? now()->parse($validated['start_date'])->addDays($action->duration_days) 
                : null),
            'notes' => $validated['notes'] ?? null,
            'status' => $action->requires_approval 
                ? DisciplineIncidentAction::STATUS_PENDING 
                : DisciplineIncidentAction::STATUS_IN_PROGRESS,
            'assigned_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Action added to incident.',
            'data' => $incidentAction->load('action'),
        ], 201);
    }

    /**
     * Approve incident action
     *
     * @authenticated
     */
    public function approveAction(Request $request, DisciplineIncidentAction $incidentAction): JsonResponse
    {
        if ($incidentAction->status !== DisciplineIncidentAction::STATUS_PENDING) {
            return response()->json([
                'message' => 'Only pending actions can be approved.',
            ], 422);
        }

        $incidentAction->approve($request->user()->id);

        return response()->json([
            'message' => 'Action approved.',
            'data' => $incidentAction->load('action'),
        ]);
    }

    /**
     * Complete incident action
     *
     * @authenticated
     */
    public function completeAction(DisciplineIncidentAction $incidentAction): JsonResponse
    {
        if ($incidentAction->status !== DisciplineIncidentAction::STATUS_IN_PROGRESS) {
            return response()->json([
                'message' => 'Only in-progress actions can be completed.',
            ], 422);
        }

        $incidentAction->complete();

        return response()->json([
            'message' => 'Action completed.',
            'data' => $incidentAction->load('action'),
        ]);
    }

    /**
     * Notify guardian about incident
     *
     * @authenticated
     */
    public function notifyGuardian(Request $request, DisciplineIncident $incident): JsonResponse
    {
        $validated = $request->validate([
            'guardian_id' => ['required', 'uuid', 'exists:guardians,id'],
            'method' => ['required', 'in:email,sms,letter,meeting'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $notification = DisciplineNotification::create([
            'incident_id' => $incident->id,
            'guardian_id' => $validated['guardian_id'],
            'method' => $validated['method'],
            'notes' => $validated['notes'] ?? null,
            'sent_at' => now(),
            'sent_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Guardian notified.',
            'data' => $notification->load('guardian'),
        ], 201);
    }

    /**
     * Get student discipline history
     *
     * @authenticated
     * @urlParam student uuid required The student ID.
     */
    public function studentHistory(Request $request, string $studentId): JsonResponse
    {
        $incidents = DisciplineIncident::where('student_id', $studentId)
            ->with(['category', 'actions.action'])
            ->orderByDesc('incident_date')
            ->get();

        $summary = [
            'total_incidents' => $incidents->count(),
            'total_points' => $incidents->sum('points_assigned'),
            'by_severity' => $incidents->groupBy('category.severity')->map->count(),
            'by_status' => $incidents->groupBy('status')->map->count(),
            'recent_incidents' => $incidents->take(5),
        ];

        return response()->json([
            'student_id' => $studentId,
            'summary' => $summary,
            'incidents' => $incidents,
        ]);
    }
}
