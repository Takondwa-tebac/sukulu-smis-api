<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\Payment;
use App\Models\StudentInvoice;
use App\Models\DisciplineIncident;
use App\Models\AdmissionApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Audit Logs
 *
 * APIs for viewing audit trail and activity logs
 */
class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:audit-logs.view');
    }

    /**
     * Get recent activity
     *
     * Get recent activity across all auditable models.
     *
     * @authenticated
     * @queryParam limit integer Number of records. Default: 50. Example: 20
     * @queryParam user_id uuid Filter by user who made changes.
     * @queryParam model string Filter by model type (student, payment, invoice, etc).
     * @queryParam from_date date Filter from date.
     * @queryParam to_date date Filter to date.
     *
     * @response 200 scenario="Success" {"data": []}
     */
    public function recentActivity(Request $request): JsonResponse
    {
        $limit = min($request->input('limit', 50), 100);
        $schoolId = $request->user()->school_id;

        $activities = collect();

        // Students
        if (!$request->model || $request->model === 'student') {
            $students = Student::query()
                ->where('school_id', $schoolId)
                ->when($request->user_id, fn($q, $id) => $q->where('created_by', $id)->orWhere('updated_by', $id))
                ->when($request->from_date, fn($q, $d) => $q->where('updated_at', '>=', $d))
                ->when($request->to_date, fn($q, $d) => $q->where('updated_at', '<=', $d))
                ->with(['creator:id,first_name,last_name', 'updater:id,first_name,last_name'])
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get()
                ->map(fn($s) => [
                    'model' => 'student',
                    'model_id' => $s->id,
                    'description' => "Student: {$s->first_name} {$s->last_name}",
                    'action' => $s->created_at->eq($s->updated_at) ? 'created' : 'updated',
                    'user' => $s->updater ?? $s->creator,
                    'timestamp' => $s->updated_at,
                ]);

            $activities = $activities->merge($students);
        }

        // Payments
        if (!$request->model || $request->model === 'payment') {
            $payments = Payment::query()
                ->where('school_id', $schoolId)
                ->when($request->user_id, fn($q, $id) => $q->where('created_by', $id))
                ->when($request->from_date, fn($q, $d) => $q->where('created_at', '>=', $d))
                ->when($request->to_date, fn($q, $d) => $q->where('created_at', '<=', $d))
                ->with(['creator:id,first_name,last_name', 'student:id,first_name,last_name'])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(fn($p) => [
                    'model' => 'payment',
                    'model_id' => $p->id,
                    'description' => "Payment: {$p->amount} for {$p->student?->first_name} {$p->student?->last_name}",
                    'action' => 'created',
                    'user' => $p->creator,
                    'timestamp' => $p->created_at,
                ]);

            $activities = $activities->merge($payments);
        }

        // Invoices
        if (!$request->model || $request->model === 'invoice') {
            $invoices = StudentInvoice::query()
                ->where('school_id', $schoolId)
                ->when($request->user_id, fn($q, $id) => $q->where('created_by', $id)->orWhere('updated_by', $id))
                ->when($request->from_date, fn($q, $d) => $q->where('updated_at', '>=', $d))
                ->when($request->to_date, fn($q, $d) => $q->where('updated_at', '<=', $d))
                ->with(['creator:id,first_name,last_name', 'student:id,first_name,last_name'])
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get()
                ->map(fn($i) => [
                    'model' => 'invoice',
                    'model_id' => $i->id,
                    'description' => "Invoice: {$i->invoice_number} - {$i->total_amount}",
                    'action' => $i->created_at->eq($i->updated_at) ? 'created' : 'updated',
                    'user' => $i->updater ?? $i->creator,
                    'timestamp' => $i->updated_at,
                ]);

            $activities = $activities->merge($invoices);
        }

        // Sort by timestamp and limit
        $activities = $activities
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();

        return response()->json([
            'data' => $activities,
            'meta' => [
                'total' => $activities->count(),
                'filters' => [
                    'user_id' => $request->user_id,
                    'model' => $request->model,
                    'from_date' => $request->from_date,
                    'to_date' => $request->to_date,
                ],
            ],
        ]);
    }

    /**
     * Get user activity
     *
     * Get all activity for a specific user.
     *
     * @authenticated
     * @urlParam user uuid required The user ID.
     */
    public function userActivity(Request $request, string $userId): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $limit = min($request->input('limit', 50), 100);

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Get counts of actions by this user
        $studentCreated = Student::where('school_id', $schoolId)->where('created_by', $userId)->count();
        $studentUpdated = Student::where('school_id', $schoolId)->where('updated_by', $userId)->count();
        $paymentsRecorded = Payment::where('school_id', $schoolId)->where('created_by', $userId)->count();
        $invoicesCreated = StudentInvoice::where('school_id', $schoolId)->where('created_by', $userId)->count();

        // Recent activity
        $recentStudents = Student::where('school_id', $schoolId)
            ->where(fn($q) => $q->where('created_by', $userId)->orWhere('updated_by', $userId))
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'updated_at']);

        $recentPayments = Payment::where('school_id', $schoolId)
            ->where('created_by', $userId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'amount', 'payment_date', 'created_at']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
            ],
            'summary' => [
                'students_created' => $studentCreated,
                'students_updated' => $studentUpdated,
                'payments_recorded' => $paymentsRecorded,
                'invoices_created' => $invoicesCreated,
            ],
            'recent_activity' => [
                'students' => $recentStudents,
                'payments' => $recentPayments,
            ],
        ]);
    }

    /**
     * Get model history
     *
     * Get change history for a specific model record.
     *
     * @authenticated
     * @queryParam model string required Model type (student, payment, invoice).
     * @queryParam id uuid required Model ID.
     */
    public function modelHistory(Request $request): JsonResponse
    {
        $request->validate([
            'model' => ['required', 'in:student,payment,invoice,discipline_incident'],
            'id' => ['required', 'uuid'],
        ]);

        $modelClass = match ($request->model) {
            'student' => Student::class,
            'payment' => Payment::class,
            'invoice' => StudentInvoice::class,
            'discipline_incident' => DisciplineIncident::class,
            default => null,
        };

        if (!$modelClass) {
            return response()->json(['message' => 'Invalid model type.'], 422);
        }

        $record = $modelClass::with(['creator:id,first_name,last_name,email', 'updater:id,first_name,last_name,email'])
            ->find($request->id);

        if (!$record) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        return response()->json([
            'model' => $request->model,
            'id' => $record->id,
            'created_at' => $record->created_at,
            'created_by' => $record->creator,
            'updated_at' => $record->updated_at,
            'updated_by' => $record->updater,
            'deleted_at' => $record->deleted_at ?? null,
        ]);
    }

    /**
     * Get activity summary
     *
     * Get summary of all activity for a date range.
     *
     * @authenticated
     * @queryParam from_date date Start date. Default: 30 days ago.
     * @queryParam to_date date End date. Default: today.
     */
    public function activitySummary(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $fromDate = $request->input('from_date', now()->subDays(30)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $summary = [
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'students' => [
                'created' => Student::where('school_id', $schoolId)
                    ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
                    ->count(),
                'updated' => Student::where('school_id', $schoolId)
                    ->whereBetween('updated_at', [$fromDate, $toDate . ' 23:59:59'])
                    ->whereColumn('created_at', '!=', 'updated_at')
                    ->count(),
            ],
            'payments' => [
                'count' => Payment::where('school_id', $schoolId)
                    ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
                    ->count(),
                'total' => Payment::where('school_id', $schoolId)
                    ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
                    ->sum('amount'),
            ],
            'invoices' => [
                'created' => StudentInvoice::where('school_id', $schoolId)
                    ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
                    ->count(),
            ],
            'most_active_users' => User::where('school_id', $schoolId)
                ->withCount(['createdStudents' => fn($q) => $q->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])])
                ->orderByDesc('created_students_count')
                ->limit(5)
                ->get(['id', 'first_name', 'last_name', 'created_students_count']),
        ];

        return response()->json($summary);
    }
}
