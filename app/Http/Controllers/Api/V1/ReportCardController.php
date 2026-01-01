<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\BulkApproveReportCardsRequest;
use App\Http\Requests\Report\BulkPublishReportCardsRequest;
use App\Http\Requests\Report\GenerateReportCardRequest;
use App\Http\Requests\Report\UpdateReportCardRequest;
use App\Http\Resources\Report\ReportCardResource;
use App\Models\Exam;
use App\Models\ReportCard;
use App\Models\ReportCardSubject;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\Grading\GradingEngine;
use App\Models\GradingSystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @group Report Cards
 *
 * APIs for managing student report cards
 */
class ReportCardController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ReportCard::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->student_id, fn ($q, $id) => $q->where('student_id', $id))
            ->when($request->academic_year_id, fn ($q, $id) => $q->where('academic_year_id', $id))
            ->when($request->term_id, fn ($q, $id) => $q->where('term_id', $id))
            ->when($request->class_id, fn ($q, $id) => $q->where('class_id', $id))
            ->with(['student', 'academicYear', 'term', 'schoolClass'])
            ->orderByDesc('created_at');

        return ReportCardResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function show(ReportCard $reportCard): ReportCardResource
    {
        return new ReportCardResource(
            $reportCard->load([
                'student',
                'academicYear',
                'term',
                'schoolClass',
                'stream',
                'subjects.subject',
            ])
        );
    }

    public function generate(GenerateReportCardRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $gradingSystem = GradingSystem::with('gradeScales')->findOrFail($validated['grading_system_id']);
        $engine = new GradingEngine($gradingSystem);

        // Get enrolled students
        $enrollmentQuery = StudentEnrollment::where('academic_year_id', $validated['academic_year_id'])
            ->where('class_id', $validated['class_id'])
            ->where('status', 'active');

        if (!empty($validated['stream_id'])) {
            $enrollmentQuery->where('stream_id', $validated['stream_id']);
        }

        if (!empty($validated['student_ids'])) {
            $enrollmentQuery->whereIn('student_id', $validated['student_ids']);
        }

        $enrollments = $enrollmentQuery->with('student')->get();

        if ($enrollments->isEmpty()) {
            return response()->json(['message' => 'No students found for the specified criteria.'], 422);
        }

        // Get exams for this term
        $exams = Exam::where('academic_year_id', $validated['academic_year_id'])
            ->where('term_id', $validated['term_id'])
            ->whereIn('status', [Exam::STATUS_COMPLETED, Exam::STATUS_PUBLISHED])
            ->with(['examSubjects.studentMarks', 'examSubjects.classSubject.subject'])
            ->get();

        $reportCards = DB::transaction(function () use ($enrollments, $exams, $validated, $engine, $request) {
            $generatedCards = [];
            $studentScores = [];

            // Calculate scores for all students first (for ranking)
            foreach ($enrollments as $enrollment) {
                $studentId = $enrollment->student_id;
                $subjectScores = [];

                foreach ($exams as $exam) {
                    foreach ($exam->examSubjects as $examSubject) {
                        if ($examSubject->classSubject->class_id !== $validated['class_id']) {
                            continue;
                        }

                        $mark = $examSubject->studentMarks->firstWhere('student_id', $studentId);
                        if ($mark && $mark->score !== null) {
                            $subjectId = $examSubject->classSubject->subject_id;
                            if (!isset($subjectScores[$subjectId])) {
                                $subjectScores[$subjectId] = [
                                    'subject' => $examSubject->classSubject->subject,
                                    'exam_score' => 0,
                                    'ca_score' => 0,
                                ];
                            }
                            // Simplified: treating all as exam scores
                            $subjectScores[$subjectId]['exam_score'] += $mark->score;
                        }
                    }
                }

                $totalScore = 0;
                foreach ($subjectScores as $score) {
                    $totalScore += $score['exam_score'];
                }

                $studentScores[$studentId] = [
                    'subjects' => $subjectScores,
                    'total' => $totalScore,
                    'average' => count($subjectScores) > 0 ? $totalScore / count($subjectScores) : 0,
                ];
            }

            // Calculate positions
            $sortedScores = collect($studentScores)->sortByDesc('average')->values();
            $positions = [];
            $rank = 0;
            $lastAvg = null;
            foreach ($sortedScores as $index => $score) {
                if ($lastAvg !== $score['average']) {
                    $rank = $index + 1;
                }
                $lastAvg = $score['average'];
                foreach ($studentScores as $studentId => $s) {
                    if ($s['average'] === $score['average'] && !isset($positions[$studentId])) {
                        $positions[$studentId] = $rank;
                    }
                }
            }

            // Generate report cards
            foreach ($enrollments as $enrollment) {
                $studentId = $enrollment->student_id;
                $scores = $studentScores[$studentId] ?? ['subjects' => [], 'total' => 0, 'average' => 0];

                // Delete existing draft report card
                ReportCard::where('student_id', $studentId)
                    ->where('academic_year_id', $validated['academic_year_id'])
                    ->where('term_id', $validated['term_id'])
                    ->where('status', ReportCard::STATUS_DRAFT)
                    ->delete();

                $overallGrade = $engine->calculateGrade($scores['average']);

                $reportCard = ReportCard::create([
                    'school_id' => $request->user()->school_id,
                    'student_id' => $studentId,
                    'academic_year_id' => $validated['academic_year_id'],
                    'term_id' => $validated['term_id'],
                    'class_id' => $validated['class_id'],
                    'stream_id' => $enrollment->stream_id,
                    'total_score' => $scores['total'],
                    'average_score' => $scores['average'],
                    'position' => $positions[$studentId] ?? null,
                    'total_students' => count($enrollments),
                    'overall_grade' => $overallGrade?->grade,
                    'status' => ReportCard::STATUS_GENERATED,
                ]);

                // Add subject results
                foreach ($scores['subjects'] as $subjectId => $subjectScore) {
                    $totalSubjectScore = $subjectScore['exam_score'] + $subjectScore['ca_score'];
                    $subjectGrade = $engine->calculateGrade($totalSubjectScore);

                    ReportCardSubject::create([
                        'report_card_id' => $reportCard->id,
                        'subject_id' => $subjectId,
                        'ca_score' => $subjectScore['ca_score'] ?: null,
                        'exam_score' => $subjectScore['exam_score'] ?: null,
                        'total_score' => $totalSubjectScore,
                        'grade' => $subjectGrade?->grade,
                        'remarks' => $subjectGrade?->remark,
                    ]);
                }

                $generatedCards[] = $reportCard;
            }

            return $generatedCards;
        });

        return response()->json([
            'message' => count($reportCards) . ' report cards generated successfully.',
            'count' => count($reportCards),
        ]);
    }

    public function update(UpdateReportCardRequest $request, ReportCard $reportCard): JsonResponse
    {
        if ($reportCard->isPublished()) {
            return response()->json(['message' => 'Published report cards cannot be edited.'], 422);
        }

        $reportCard->update($request->validated());

        return response()->json([
            'message' => 'Report card updated successfully.',
            'data' => new ReportCardResource($reportCard),
        ]);
    }

    public function approve(Request $request, ReportCard $reportCard): JsonResponse
    {
        if ($reportCard->status !== ReportCard::STATUS_GENERATED) {
            return response()->json(['message' => 'Only generated report cards can be approved.'], 422);
        }

        $reportCard->approve($request->user()->id);

        return response()->json([
            'message' => 'Report card approved successfully.',
            'data' => new ReportCardResource($reportCard),
        ]);
    }

    public function bulkApprove(BulkApproveReportCardsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $count = ReportCard::whereIn('id', $validated['report_card_ids'])
            ->where('status', ReportCard::STATUS_GENERATED)
            ->update([
                'status' => ReportCard::STATUS_APPROVED,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

        return response()->json([
            'message' => "{$count} report cards approved successfully.",
        ]);
    }

    public function publish(ReportCard $reportCard): JsonResponse
    {
        if ($reportCard->status !== ReportCard::STATUS_APPROVED) {
            return response()->json(['message' => 'Only approved report cards can be published.'], 422);
        }

        $reportCard->publish();

        return response()->json([
            'message' => 'Report card published successfully.',
            'data' => new ReportCardResource($reportCard),
        ]);
    }

    public function bulkPublish(BulkPublishReportCardsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $count = ReportCard::whereIn('id', $validated['report_card_ids'])
            ->where('status', ReportCard::STATUS_APPROVED)
            ->update([
                'status' => ReportCard::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);

        return response()->json([
            'message' => "{$count} report cards published successfully.",
        ]);
    }

    public function studentReports(Student $student): AnonymousResourceCollection
    {
        $reportCards = ReportCard::where('student_id', $student->id)
            ->published()
            ->with(['academicYear', 'term', 'schoolClass', 'subjects.subject'])
            ->orderByDesc('created_at')
            ->get();

        return ReportCardResource::collection($reportCards);
    }
}
