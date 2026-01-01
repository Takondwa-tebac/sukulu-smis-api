<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\AddExamSubjectsRequest;
use App\Http\Requests\Exam\StoreExamRequest;
use App\Http\Requests\Exam\UpdateExamRequest;
use App\Http\Resources\Exam\ExamResource;
use App\Models\Exam;
use App\Models\ExamSubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @group Exams
 *
 * APIs for managing exams
 */
class ExamController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Exam::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->term_id, fn ($q, $id) => $q->where('term_id', $id))
            ->when($request->academic_year_id, fn ($q, $id) => $q->where('academic_year_id', $id))
            ->when($request->exam_type_id, fn ($q, $id) => $q->where('exam_type_id', $id))
            ->with(['academicYear', 'term', 'examType'])
            ->orderByDesc('start_date');

        return ExamResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(StoreExamRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $exam = DB::transaction(function () use ($validated) {
            $examData = collect($validated)->except('subjects')->toArray();
            $exam = Exam::create($examData);

            if (!empty($validated['subjects'])) {
                foreach ($validated['subjects'] as $subject) {
                    ExamSubject::create([
                        'exam_id' => $exam->id,
                        'class_subject_id' => $subject['class_subject_id'],
                        'exam_date' => $subject['exam_date'] ?? null,
                        'start_time' => $subject['start_time'] ?? null,
                        'duration_minutes' => $subject['duration_minutes'] ?? null,
                        'max_score' => $subject['max_score'] ?? $exam->max_score,
                        'venue' => $subject['venue'] ?? null,
                    ]);
                }
            }

            return $exam;
        });

        return (new ExamResource($exam->load(['academicYear', 'term', 'examType', 'examSubjects'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Exam $exam): ExamResource
    {
        return new ExamResource(
            $exam->load(['academicYear', 'term', 'examType', 'examSubjects.classSubject.subject'])
        );
    }

    public function update(UpdateExamRequest $request, Exam $exam): ExamResource
    {
        $exam->update($request->validated());

        return new ExamResource($exam);
    }

    public function destroy(Exam $exam): JsonResponse
    {
        if ($exam->status !== Exam::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Only draft exams can be deleted.',
            ], 422);
        }

        $exam->delete();

        return response()->json(['message' => 'Exam deleted successfully.']);
    }

    public function publish(Exam $exam): JsonResponse
    {
        if ($exam->status !== Exam::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Only completed exams can be published.',
            ], 422);
        }

        $exam->update(['status' => Exam::STATUS_PUBLISHED]);

        return response()->json([
            'message' => 'Exam published successfully.',
            'data' => new ExamResource($exam),
        ]);
    }

    public function addSubjects(AddExamSubjectsRequest $request, Exam $exam): JsonResponse
    {
        $validated = $request->validated();

        foreach ($validated['subjects'] as $subject) {
            ExamSubject::updateOrCreate(
                [
                    'exam_id' => $exam->id,
                    'class_subject_id' => $subject['class_subject_id'],
                ],
                [
                    'exam_date' => $subject['exam_date'] ?? null,
                    'start_time' => $subject['start_time'] ?? null,
                    'duration_minutes' => $subject['duration_minutes'] ?? null,
                    'max_score' => $subject['max_score'] ?? $exam->max_score,
                    'venue' => $subject['venue'] ?? null,
                ]
            );
        }

        return response()->json([
            'message' => 'Subjects added successfully.',
            'data' => new ExamResource($exam->load('examSubjects')),
        ]);
    }
}
