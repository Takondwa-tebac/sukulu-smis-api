<?php

namespace App\Jobs;

use App\Models\Exam;
use App\Models\GradingSystem;
use App\Models\ReportCard;
use App\Models\ReportCardSubject;
use App\Models\StudentEnrollment;
use App\Models\StudentMark;
use App\Services\Grading\GradingEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateReportCardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        protected string $examId,
        protected ?string $classId = null,
        protected ?string $streamId = null,
        protected ?string $userId = null
    ) {}

    public function handle(): void
    {
        Log::info('Starting report card generation job', [
            'exam_id' => $this->examId,
            'class_id' => $this->classId,
        ]);

        $exam = Exam::with(['term', 'academicYear', 'examSubjects.classSubject.subject'])
            ->findOrFail($this->examId);

        $gradingSystem = GradingSystem::where('school_id', $exam->school_id)
            ->where('is_default', true)
            ->first();

        if (!$gradingSystem) {
            Log::error('No default grading system found for school', ['school_id' => $exam->school_id]);
            return;
        }

        $gradingEngine = new GradingEngine($gradingSystem);

        $enrollmentsQuery = StudentEnrollment::where('academic_year_id', $exam->academic_year_id)
            ->where('status', 'active')
            ->when($this->classId, fn ($q) => $q->where('class_id', $this->classId))
            ->when($this->streamId, fn ($q) => $q->where('stream_id', $this->streamId))
            ->with('student');

        $enrollmentsQuery->chunk(50, function ($enrollments) use ($exam, $gradingEngine) {
            foreach ($enrollments as $enrollment) {
                $this->generateForStudent($enrollment, $exam, $gradingEngine);
            }
        });

        Log::info('Report card generation job completed', ['exam_id' => $this->examId]);
    }

    protected function generateForStudent($enrollment, Exam $exam, GradingEngine $gradingEngine): void
    {
        DB::transaction(function () use ($enrollment, $exam, $gradingEngine) {
            $existingCard = ReportCard::where('student_id', $enrollment->student_id)
                ->where('exam_id', $exam->id)
                ->first();

            if ($existingCard) {
                return;
            }

            $marks = StudentMark::whereHas('examSubject', fn ($q) => $q->where('exam_id', $exam->id))
                ->where('student_id', $enrollment->student_id)
                ->with('examSubject.classSubject.subject')
                ->get();

            if ($marks->isEmpty()) {
                return;
            }

            $subjectResults = [];
            $totalScore = 0;
            $totalSubjects = 0;

            foreach ($marks as $mark) {
                $grade = $gradingEngine->calculateGrade($mark->total_score);
                $subjectResults[] = [
                    'subject_id' => $mark->examSubject->classSubject->subject_id,
                    'subject_name' => $mark->examSubject->classSubject->subject->name,
                    'score' => $mark->total_score,
                    'grade' => $grade['grade'],
                    'points' => $grade['points'],
                    'remarks' => $grade['remarks'],
                ];
                $totalScore += $mark->total_score;
                $totalSubjects++;
            }

            $averageScore = $totalSubjects > 0 ? round($totalScore / $totalSubjects, 2) : 0;
            $overallGrade = $gradingEngine->calculateGrade($averageScore);
            $gpa = $gradingEngine->calculateGPA(collect($subjectResults)->pluck('points')->toArray());

            $reportCard = ReportCard::create([
                'school_id' => $exam->school_id,
                'student_id' => $enrollment->student_id,
                'academic_year_id' => $exam->academic_year_id,
                'term_id' => $exam->term_id,
                'exam_id' => $exam->id,
                'class_id' => $enrollment->class_id,
                'stream_id' => $enrollment->stream_id,
                'total_score' => $totalScore,
                'average_score' => $averageScore,
                'grade' => $overallGrade['grade'],
                'gpa' => $gpa,
                'total_subjects' => $totalSubjects,
                'status' => ReportCard::STATUS_GENERATED,
            ]);

            foreach ($subjectResults as $result) {
                ReportCardSubject::create([
                    'report_card_id' => $reportCard->id,
                    'subject_id' => $result['subject_id'],
                    'score' => $result['score'],
                    'grade' => $result['grade'],
                    'points' => $result['points'],
                    'remarks' => $result['remarks'],
                ]);
            }
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Report card generation job failed', [
            'exam_id' => $this->examId,
            'error' => $exception->getMessage(),
        ]);
    }
}
