<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\Exam;
use App\Models\Payment;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\StudentMark;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Analytics
 *
 * APIs for dashboard statistics and analytics
 */
class AnalyticsController extends Controller
{
    public function schoolAdminDashboard(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $academicYearId = $request->input('academic_year_id');
        $termId = $request->input('term_id');

        $stats = [
            'students' => $this->getStudentStats($schoolId, $academicYearId),
            'staff' => $this->getStaffStats($schoolId),
            'academics' => $this->getAcademicStats($schoolId, $academicYearId, $termId),
            'finance' => $this->getFinanceStats($schoolId, $academicYearId, $termId),
            'attendance' => $this->getAttendanceStats($schoolId, $academicYearId, $termId),
        ];

        return response()->json($stats);
    }

    public function teacherDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $stats = [
            'my_classes' => $this->getTeacherClasses($user->id),
            'pending_marks' => $this->getPendingMarksCount($user->id),
            'today_schedule' => $this->getTodaySchedule($user->id),
            'recent_exams' => $this->getRecentExams($schoolId),
        ];

        return response()->json($stats);
    }

    public function bursarDashboard(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $academicYearId = $request->input('academic_year_id');
        $termId = $request->input('term_id');

        $stats = [
            'finance_summary' => $this->getFinanceStats($schoolId, $academicYearId, $termId),
            'recent_payments' => $this->getRecentPayments($schoolId, 10),
            'outstanding_invoices' => $this->getOutstandingInvoices($schoolId),
            'payment_trends' => $this->getPaymentTrends($schoolId),
        ];

        return response()->json($stats);
    }

    public function examsOfficerDashboard(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $academicYearId = $request->input('academic_year_id');
        $termId = $request->input('term_id');

        $stats = [
            'exams_summary' => $this->getExamsSummary($schoolId, $academicYearId, $termId),
            'marks_progress' => $this->getMarksProgress($schoolId, $academicYearId, $termId),
            'report_cards_status' => $this->getReportCardsStatus($schoolId, $academicYearId, $termId),
            'grade_distribution' => $this->getGradeDistribution($schoolId, $academicYearId, $termId),
        ];

        return response()->json($stats);
    }

    protected function getStudentStats(string $schoolId, ?string $academicYearId): array
    {
        $totalStudents = Student::where('school_id', $schoolId)->count();
        
        $enrolledQuery = StudentEnrollment::where('status', 'active');
        if ($academicYearId) {
            $enrolledQuery->where('academic_year_id', $academicYearId);
        }
        $enrolledStudents = $enrolledQuery->count();

        $genderDistribution = Student::where('school_id', $schoolId)
            ->selectRaw('gender, count(*) as count')
            ->groupBy('gender')
            ->pluck('count', 'gender');

        return [
            'total' => $totalStudents,
            'enrolled' => $enrolledStudents,
            'by_gender' => $genderDistribution,
        ];
    }

    protected function getStaffStats(string $schoolId): array
    {
        $totalStaff = User::where('school_id', $schoolId)->count();
        $activeStaff = User::where('school_id', $schoolId)->where('is_active', true)->count();

        $byRole = User::where('school_id', $schoolId)
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->selectRaw('roles.name, count(*) as count')
            ->groupBy('roles.name')
            ->pluck('count', 'name');

        return [
            'total' => $totalStaff,
            'active' => $activeStaff,
            'by_role' => $byRole,
        ];
    }

    protected function getAcademicStats(string $schoolId, ?string $academicYearId, ?string $termId): array
    {
        $examsQuery = Exam::query();
        if ($academicYearId) {
            $examsQuery->where('academic_year_id', $academicYearId);
        }
        if ($termId) {
            $examsQuery->where('term_id', $termId);
        }

        return [
            'total_exams' => $examsQuery->count(),
            'published_exams' => (clone $examsQuery)->where('status', 'published')->count(),
        ];
    }

    protected function getFinanceStats(string $schoolId, ?string $academicYearId, ?string $termId): array
    {
        $invoiceQuery = StudentInvoice::where('school_id', $schoolId);
        $paymentQuery = Payment::where('school_id', $schoolId);

        if ($academicYearId) {
            $invoiceQuery->where('academic_year_id', $academicYearId);
            $paymentQuery->whereHas('invoice', fn ($q) => $q->where('academic_year_id', $academicYearId));
        }
        if ($termId) {
            $invoiceQuery->where('term_id', $termId);
            $paymentQuery->whereHas('invoice', fn ($q) => $q->where('term_id', $termId));
        }

        $totalBilled = (clone $invoiceQuery)->sum('total_amount');
        $totalPaid = (clone $invoiceQuery)->sum('paid_amount');
        $totalBalance = (clone $invoiceQuery)->sum('balance');

        return [
            'total_billed' => $totalBilled,
            'total_collected' => $totalPaid,
            'total_outstanding' => $totalBalance,
            'collection_rate' => $totalBilled > 0 ? round(($totalPaid / $totalBilled) * 100, 2) : 0,
        ];
    }

    protected function getAttendanceStats(string $schoolId, ?string $academicYearId, ?string $termId): array
    {
        $sessionQuery = AttendanceSession::where('school_id', $schoolId);
        if ($academicYearId) {
            $sessionQuery->where('academic_year_id', $academicYearId);
        }
        if ($termId) {
            $sessionQuery->where('term_id', $termId);
        }

        $sessionIds = $sessionQuery->pluck('id');
        
        $attendanceStats = StudentAttendance::whereIn('attendance_session_id', $sessionIds)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $total = $attendanceStats->sum();
        $present = ($attendanceStats['present'] ?? 0) + ($attendanceStats['late'] ?? 0);

        return [
            'total_records' => $total,
            'by_status' => $attendanceStats,
            'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
        ];
    }

    protected function getTeacherClasses(string $teacherId): array
    {
        return DB::table('timetable_slots')
            ->join('timetables', 'timetable_slots.timetable_id', '=', 'timetables.id')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('timetable_slots.teacher_id', $teacherId)
            ->where('timetables.status', 'active')
            ->select('classes.id', 'classes.name')
            ->distinct()
            ->get()
            ->toArray();
    }

    protected function getPendingMarksCount(string $teacherId): int
    {
        return StudentMark::whereHas('examSubject.exam', fn ($q) => $q->where('status', 'published'))
            ->whereNull('grade')
            ->count();
    }

    protected function getTodaySchedule(string $teacherId): array
    {
        $dayOfWeek = strtolower(now()->format('l'));

        return DB::table('timetable_slots')
            ->join('timetables', 'timetable_slots.timetable_id', '=', 'timetables.id')
            ->join('time_periods', 'timetable_slots.time_period_id', '=', 'time_periods.id')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->leftJoin('class_subjects', 'timetable_slots.class_subject_id', '=', 'class_subjects.id')
            ->leftJoin('subjects', 'class_subjects.subject_id', '=', 'subjects.id')
            ->where('timetable_slots.teacher_id', $teacherId)
            ->where('timetable_slots.day_of_week', $dayOfWeek)
            ->where('timetables.status', 'active')
            ->orderBy('time_periods.start_time')
            ->select([
                'time_periods.name as period',
                'time_periods.start_time',
                'time_periods.end_time',
                'classes.name as class',
                'subjects.name as subject',
                'timetable_slots.room',
            ])
            ->get()
            ->toArray();
    }

    protected function getRecentExams(string $schoolId, int $limit = 5): array
    {
        return Exam::where('school_id', $schoolId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'name', 'status', 'start_date', 'end_date'])
            ->toArray();
    }

    protected function getRecentPayments(string $schoolId, int $limit = 10): array
    {
        return Payment::where('school_id', $schoolId)
            ->with('student:id,first_name,last_name')
            ->orderByDesc('payment_date')
            ->limit($limit)
            ->get(['id', 'student_id', 'amount', 'payment_method', 'payment_date', 'receipt_number'])
            ->toArray();
    }

    protected function getOutstandingInvoices(string $schoolId): array
    {
        return StudentInvoice::where('school_id', $schoolId)
            ->where('balance', '>', 0)
            ->where('status', '!=', 'void')
            ->selectRaw('count(*) as count, sum(balance) as total')
            ->first()
            ->toArray();
    }

    protected function getPaymentTrends(string $schoolId): array
    {
        return Payment::where('school_id', $schoolId)
            ->where('payment_date', '>=', now()->subMonths(6))
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as month, sum(amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();
    }

    protected function getExamsSummary(string $schoolId, ?string $academicYearId, ?string $termId): array
    {
        $query = Exam::where('school_id', $schoolId);
        if ($academicYearId) $query->where('academic_year_id', $academicYearId);
        if ($termId) $query->where('term_id', $termId);

        return [
            'total' => $query->count(),
            'by_status' => (clone $query)->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ];
    }

    protected function getMarksProgress(string $schoolId, ?string $academicYearId, ?string $termId): array
    {
        $examIds = Exam::where('school_id', $schoolId)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->pluck('id');

        $marksStats = StudentMark::whereHas('examSubject', fn ($q) => $q->whereIn('exam_id', $examIds))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return $marksStats->toArray();
    }

    protected function getReportCardsStatus(string $schoolId, ?string $academicYearId, ?string $termId): array
    {
        $query = ReportCard::where('school_id', $schoolId);
        if ($academicYearId) $query->where('academic_year_id', $academicYearId);
        if ($termId) $query->where('term_id', $termId);

        return (clone $query)->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    protected function getGradeDistribution(string $schoolId, ?string $academicYearId, ?string $termId): array
    {
        $query = ReportCard::where('school_id', $schoolId);
        if ($academicYearId) $query->where('academic_year_id', $academicYearId);
        if ($termId) $query->where('term_id', $termId);

        return (clone $query)->selectRaw('grade, count(*) as count')
            ->groupBy('grade')
            ->pluck('count', 'grade')
            ->toArray();
    }
}
