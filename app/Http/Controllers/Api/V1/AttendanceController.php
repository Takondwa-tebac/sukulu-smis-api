<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\RecordAttendanceRequest;
use App\Http\Requests\Attendance\StoreAttendanceSessionRequest;
use App\Http\Resources\Attendance\AttendanceSessionResource;
use App\Http\Resources\Attendance\StudentAttendanceResource;
use App\Models\AttendanceSession;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @group Attendance
 *
 * APIs for managing student attendance
 */
class AttendanceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AttendanceSession::query()
            ->when($request->class_id, fn ($q, $id) => $q->where('class_id', $id))
            ->when($request->stream_id, fn ($q, $id) => $q->where('stream_id', $id))
            ->when($request->date, fn ($q, $date) => $q->whereDate('date', $date))
            ->when($request->from_date, fn ($q, $date) => $q->whereDate('date', '>=', $date))
            ->when($request->to_date, fn ($q, $date) => $q->whereDate('date', '<=', $date))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->with(['schoolClass', 'stream', 'takenByUser'])
            ->withCount('attendances')
            ->orderByDesc('date');

        return AttendanceSessionResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(StoreAttendanceSessionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Check if session already exists
        $existing = AttendanceSession::where('school_id', $request->user()->school_id)
            ->where('class_id', $validated['class_id'])
            ->where('stream_id', $validated['stream_id'] ?? null)
            ->whereDate('date', $validated['date'])
            ->where('session_type', $validated['session_type'] ?? AttendanceSession::SESSION_FULL_DAY)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Attendance session already exists for this class/date.',
                'data' => new AttendanceSessionResource($existing),
            ], 422);
        }

        $session = AttendanceSession::create([
            'school_id' => $request->user()->school_id,
            'taken_by' => $request->user()->id,
            ...$validated,
        ]);

        return (new AttendanceSessionResource($session->load(['schoolClass', 'stream'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AttendanceSession $attendanceSession): AttendanceSessionResource
    {
        return new AttendanceSessionResource(
            $attendanceSession->load([
                'academicYear',
                'term',
                'schoolClass',
                'stream',
                'takenByUser',
                'attendances.student',
            ])
        );
    }

    public function recordAttendance(RecordAttendanceRequest $request, AttendanceSession $attendanceSession): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $attendanceSession, $request) {
            foreach ($validated['attendances'] as $attendance) {
                StudentAttendance::updateOrCreate(
                    [
                        'attendance_session_id' => $attendanceSession->id,
                        'student_id' => $attendance['student_id'],
                    ],
                    [
                        'status' => $attendance['status'],
                        'arrival_time' => $attendance['arrival_time'] ?? null,
                        'departure_time' => $attendance['departure_time'] ?? null,
                        'absence_reason' => $attendance['absence_reason'] ?? null,
                        'notes' => $attendance['notes'] ?? null,
                    ]
                );
            }

            $attendanceSession->update([
                'taken_by' => $request->user()->id,
                'taken_at' => now(),
                'status' => AttendanceSession::STATUS_COMPLETED,
            ]);
        });

        return response()->json([
            'message' => 'Attendance recorded successfully.',
            'data' => new AttendanceSessionResource($attendanceSession->load('attendances.student')),
        ]);
    }

    public function getStudentsForAttendance(Request $request, AttendanceSession $attendanceSession): JsonResponse
    {
        // Get enrolled students for this class
        $enrollments = StudentEnrollment::where('academic_year_id', $attendanceSession->academic_year_id)
            ->where('class_id', $attendanceSession->class_id)
            ->when($attendanceSession->stream_id, fn ($q) => $q->where('stream_id', $attendanceSession->stream_id))
            ->where('status', 'active')
            ->with('student')
            ->get();

        // Get existing attendance records
        $existingAttendance = $attendanceSession->attendances->keyBy('student_id');

        $students = $enrollments->map(function ($enrollment) use ($existingAttendance) {
            $attendance = $existingAttendance->get($enrollment->student_id);
            return [
                'student_id' => $enrollment->student_id,
                'student' => $enrollment->student,
                'status' => $attendance?->status ?? null,
                'arrival_time' => $attendance?->arrival_time?->format('H:i'),
                'notes' => $attendance?->notes,
            ];
        });

        return response()->json([
            'session' => new AttendanceSessionResource($attendanceSession),
            'students' => $students,
        ]);
    }

    public function studentAttendanceReport(Request $request, Student $student): JsonResponse
    {
        $query = StudentAttendance::where('student_id', $student->id)
            ->when($request->from_date, fn ($q, $date) => $q->whereHas('attendanceSession', fn ($s) => $s->whereDate('date', '>=', $date)))
            ->when($request->to_date, fn ($q, $date) => $q->whereHas('attendanceSession', fn ($s) => $s->whereDate('date', '<=', $date)))
            ->when($request->academic_year_id, fn ($q, $id) => $q->whereHas('attendanceSession', fn ($s) => $s->where('academic_year_id', $id)))
            ->when($request->term_id, fn ($q, $id) => $q->whereHas('attendanceSession', fn ($s) => $s->where('term_id', $id)));

        $attendances = $query->with('attendanceSession')->get();

        $summary = [
            'total_days' => $attendances->count(),
            'present' => $attendances->where('status', StudentAttendance::STATUS_PRESENT)->count(),
            'absent' => $attendances->where('status', StudentAttendance::STATUS_ABSENT)->count(),
            'late' => $attendances->where('status', StudentAttendance::STATUS_LATE)->count(),
            'excused' => $attendances->where('status', StudentAttendance::STATUS_EXCUSED)->count(),
        ];

        $summary['attendance_percentage'] = $summary['total_days'] > 0
            ? round(($summary['present'] + $summary['late']) / $summary['total_days'] * 100, 2)
            : 0;

        return response()->json([
            'student_id' => $student->id,
            'student_name' => $student->full_name,
            'summary' => $summary,
            'records' => StudentAttendanceResource::collection($attendances),
        ]);
    }

    public function classAttendanceReport(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => ['required', 'uuid', 'exists:classes,id'],
            'date' => ['required', 'date'],
        ]);

        $session = AttendanceSession::where('class_id', $request->class_id)
            ->whereDate('date', $request->date)
            ->with(['attendances.student', 'schoolClass', 'stream'])
            ->first();

        if (!$session) {
            return response()->json([
                'message' => 'No attendance session found for this class/date.',
            ], 404);
        }

        return response()->json([
            'session' => new AttendanceSessionResource($session),
            'summary' => $session->getAttendanceSummary(),
        ]);
    }
}
