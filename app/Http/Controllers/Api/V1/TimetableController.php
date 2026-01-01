<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timetable\StoreTimetableRequest;
use App\Http\Requests\Timetable\StoreTimetableSlotRequest;
use App\Http\Resources\Timetable\TimetableResource;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @group Timetables
 *
 * APIs for managing class timetables
 */
class TimetableController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Timetable::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->class_id, fn ($q, $id) => $q->where('class_id', $id))
            ->when($request->stream_id, fn ($q, $id) => $q->where('stream_id', $id))
            ->when($request->academic_year_id, fn ($q, $id) => $q->where('academic_year_id', $id))
            ->when($request->term_id, fn ($q, $id) => $q->where('term_id', $id))
            ->with(['academicYear', 'term', 'schoolClass', 'stream'])
            ->orderByDesc('created_at');

        return TimetableResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(StoreTimetableRequest $request): JsonResponse
    {
        $timetable = Timetable::create([
            'school_id' => $request->user()->school_id,
            ...$request->validated(),
        ]);

        return (new TimetableResource($timetable->load(['academicYear', 'term', 'schoolClass', 'stream'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Timetable $timetable): TimetableResource
    {
        return new TimetableResource(
            $timetable->load([
                'academicYear',
                'term',
                'schoolClass',
                'stream',
                'slots.timePeriod',
                'slots.classSubject.subject',
                'slots.teacher',
            ])
        );
    }

    public function update(StoreTimetableRequest $request, Timetable $timetable): TimetableResource
    {
        $timetable->update($request->validated());

        return new TimetableResource($timetable);
    }

    public function destroy(Timetable $timetable): JsonResponse
    {
        if ($timetable->status === Timetable::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'Cannot delete an active timetable. Archive it first.',
            ], 422);
        }

        $timetable->delete();

        return response()->json(['message' => 'Timetable deleted successfully.']);
    }

    public function activate(Timetable $timetable): JsonResponse
    {
        $timetable->activate();

        return response()->json([
            'message' => 'Timetable activated successfully.',
            'data' => new TimetableResource($timetable),
        ]);
    }

    public function addSlots(StoreTimetableSlotRequest $request, Timetable $timetable): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $timetable) {
            foreach ($validated['slots'] as $slotData) {
                TimetableSlot::updateOrCreate(
                    [
                        'timetable_id' => $timetable->id,
                        'time_period_id' => $slotData['time_period_id'],
                        'day_of_week' => $slotData['day_of_week'],
                    ],
                    [
                        'class_subject_id' => $slotData['class_subject_id'] ?? null,
                        'teacher_id' => $slotData['teacher_id'] ?? null,
                        'room' => $slotData['room'] ?? null,
                        'notes' => $slotData['notes'] ?? null,
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Slots added successfully.',
            'data' => new TimetableResource($timetable->load('slots.timePeriod')),
        ]);
    }

    public function getByDay(Timetable $timetable, string $day): JsonResponse
    {
        $slots = $timetable->slots()
            ->forDay($day)
            ->with(['timePeriod', 'classSubject.subject', 'teacher'])
            ->get()
            ->sortBy('timePeriod.sort_order');

        return response()->json([
            'day' => $day,
            'slots' => $slots,
        ]);
    }

    public function getTeacherSchedule(Request $request): JsonResponse
    {
        $teacherId = $request->input('teacher_id', $request->user()->id);

        $slots = TimetableSlot::forTeacher($teacherId)
            ->whereHas('timetable', fn ($q) => $q->active())
            ->with([
                'timetable.schoolClass',
                'timetable.stream',
                'timePeriod',
                'classSubject.subject',
            ])
            ->get()
            ->groupBy('day_of_week');

        return response()->json([
            'teacher_id' => $teacherId,
            'schedule' => $slots,
        ]);
    }
}
