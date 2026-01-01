<?php

namespace App\Jobs;

use App\Models\ExamSubject;
use App\Models\GradingSystem;
use App\Models\StudentMark;
use App\Services\Grading\GradingEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateGradesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        protected string $examSubjectId
    ) {}

    public function handle(): void
    {
        Log::info('Starting grade calculation job', ['exam_subject_id' => $this->examSubjectId]);

        $examSubject = ExamSubject::with('exam')->findOrFail($this->examSubjectId);

        $gradingSystem = GradingSystem::where('school_id', $examSubject->exam->school_id)
            ->where('is_default', true)
            ->first();

        if (!$gradingSystem) {
            Log::error('No default grading system found');
            return;
        }

        $gradingEngine = new GradingEngine($gradingSystem);

        StudentMark::where('exam_subject_id', $this->examSubjectId)
            ->chunk(100, function ($marks) use ($gradingEngine) {
                foreach ($marks as $mark) {
                    $grade = $gradingEngine->calculateGrade($mark->total_score);
                    $mark->update([
                        'grade' => $grade['grade'],
                        'points' => $grade['points'],
                        'remarks' => $grade['remarks'],
                    ]);
                }
            });

        Log::info('Grade calculation job completed', ['exam_subject_id' => $this->examSubjectId]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Grade calculation job failed', [
            'exam_subject_id' => $this->examSubjectId,
            'error' => $exception->getMessage(),
        ]);
    }
}
