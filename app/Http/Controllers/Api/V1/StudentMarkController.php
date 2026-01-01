<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\BulkModerateRequest;
use App\Http\Requests\Exam\ModerateMarkRequest;
use App\Http\Requests\Exam\StoreStudentMarksRequest;
use App\Http\Resources\Exam\StudentMarkResource;
use App\Jobs\CalculateGradesJob;
use App\Models\ExamSubject;
use App\Models\StudentMark;
use App\Services\Grading\GradingEngine;
use App\Models\GradingSystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @group Student Marks
 *
 * APIs for managing student marks and moderation
 */
class StudentMarkController extends Controller
{
    public function index(Request $request, ExamSubject $examSubject): AnonymousResourceCollection
    {
        $query = $examSubject->studentMarks()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->with(['student', 'enteredByUser'])
            ->orderBy('created_at');

        return StudentMarkResource::collection($query->paginate($request->input('per_page', 50)));
    }

    public function store(StoreStudentMarksRequest $request, ExamSubject $examSubject): JsonResponse
    {
        if (!$examSubject->canEnterMarks()) {
            return response()->json([
                'message' => 'Marks cannot be entered for this exam subject in its current status.',
            ], 422);
        }

        $validated = $request->validated();

        $marks = DB::transaction(function () use ($validated, $examSubject, $request) {
            $createdMarks = [];

            foreach ($validated['marks'] as $markData) {
                $mark = StudentMark::updateOrCreate(
                    [
                        'exam_subject_id' => $examSubject->id,
                        'student_id' => $markData['student_id'],
                    ],
                    [
                        'school_id' => $request->user()->school_id,
                        'score' => $markData['is_absent'] ?? false ? null : ($markData['score'] ?? null),
                        'is_absent' => $markData['is_absent'] ?? false,
                        'absent_reason' => $markData['absent_reason'] ?? null,
                        'remarks' => $markData['remarks'] ?? null,
                        'entered_by' => $request->user()->id,
                        'entered_at' => now(),
                        'status' => StudentMark::STATUS_DRAFT,
                    ]
                );

                $createdMarks[] = $mark;
            }

            return $createdMarks;
        });

        return response()->json([
            'message' => 'Marks saved successfully.',
            'count' => count($marks),
        ]);
    }

    public function submitMarks(Request $request, ExamSubject $examSubject): JsonResponse
    {
        $examSubject->studentMarks()
            ->where('status', StudentMark::STATUS_DRAFT)
            ->update(['status' => StudentMark::STATUS_SUBMITTED]);

        $examSubject->update(['status' => ExamSubject::STATUS_MARKS_ENTERED]);

        return response()->json([
            'message' => 'Marks submitted for moderation.',
        ]);
    }

    public function moderate(ModerateMarkRequest $request, StudentMark $studentMark): JsonResponse
    {
        if ($studentMark->isLocked()) {
            return response()->json([
                'message' => 'This mark is locked and cannot be moderated.',
            ], 422);
        }

        $validated = $request->validated();

        $studentMark->moderate(
            $validated['score'],
            $validated['reason'],
            $request->user()->id
        );

        return response()->json([
            'message' => 'Mark moderated successfully.',
            'data' => new StudentMarkResource($studentMark->load('moderationLogs')),
        ]);
    }

    public function bulkModerate(BulkModerateRequest $request, ExamSubject $examSubject): JsonResponse
    {
        if (!$examSubject->canModerate()) {
            return response()->json([
                'message' => 'This exam subject cannot be moderated in its current status.',
            ], 422);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
            foreach ($validated['moderations'] as $moderation) {
                $mark = StudentMark::find($moderation['student_mark_id']);
                if ($mark && !$mark->isLocked()) {
                    $mark->moderate(
                        $moderation['score'],
                        $moderation['reason'],
                        $request->user()->id
                    );
                }
            }
        });

        $examSubject->update(['status' => ExamSubject::STATUS_MODERATED]);

        return response()->json([
            'message' => 'Marks moderated successfully.',
        ]);
    }

    public function approve(Request $request, ExamSubject $examSubject): JsonResponse
    {
        if (!$examSubject->canApprove()) {
            return response()->json([
                'message' => 'This exam subject cannot be approved in its current status.',
            ], 422);
        }

        DB::transaction(function () use ($examSubject, $request) {
            $examSubject->studentMarks()
                ->whereIn('status', [StudentMark::STATUS_SUBMITTED, StudentMark::STATUS_MODERATED])
                ->each(function ($mark) use ($request) {
                    $mark->approve($request->user()->id);
                });

            $examSubject->update(['status' => ExamSubject::STATUS_APPROVED]);
        });

        return response()->json([
            'message' => 'Marks approved successfully.',
        ]);
    }

    public function lock(ExamSubject $examSubject): JsonResponse
    {
        if ($examSubject->status !== ExamSubject::STATUS_APPROVED) {
            return response()->json([
                'message' => 'Only approved marks can be locked.',
            ], 422);
        }

        DB::transaction(function () use ($examSubject) {
            $examSubject->studentMarks()->update(['status' => StudentMark::STATUS_LOCKED]);
            $examSubject->update(['status' => ExamSubject::STATUS_LOCKED]);
        });

        return response()->json([
            'message' => 'Marks locked successfully.',
        ]);
    }

    public function calculateGrades(Request $request, ExamSubject $examSubject): JsonResponse
    {
        $request->validate([
            'async' => ['boolean'],
        ]);

        if ($request->boolean('async', false)) {
            CalculateGradesJob::dispatch($examSubject->id);

            return response()->json([
                'message' => 'Grade calculation job has been queued.',
            ]);
        }

        $gradingSystem = GradingSystem::where('school_id', $examSubject->exam->school_id)
            ->where('is_default', true)
            ->with('gradeScales')
            ->first();

        if (!$gradingSystem) {
            return response()->json([
                'message' => 'No default grading system found for this school.',
            ], 422);
        }

        $engine = new GradingEngine($gradingSystem);

        $updated = 0;
        $examSubject->studentMarks()->whereNotNull('score')->each(function ($mark) use ($engine, &$updated) {
            $grade = $engine->calculateGrade($mark->score);
            if ($grade) {
                $mark->update(['grade' => $grade->grade]);
                $updated++;
            }
        });

        return response()->json([
            'message' => "Grades calculated for {$updated} marks.",
        ]);
    }
}
