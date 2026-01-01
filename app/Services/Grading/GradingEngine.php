<?php

namespace App\Services\Grading;

use App\Models\GradeScale;
use App\Models\GradingSystem;
use InvalidArgumentException;

class GradingEngine implements GradingEngineInterface
{
    protected ?GradingSystem $gradingSystem = null;

    public function __construct(?GradingSystem $gradingSystem = null)
    {
        if ($gradingSystem) {
            $this->setGradingSystem($gradingSystem);
        }
    }

    public function setGradingSystem(GradingSystem $gradingSystem): self
    {
        $this->gradingSystem = $gradingSystem->load('gradeScales');
        return $this;
    }

    protected function ensureGradingSystem(): void
    {
        if (!$this->gradingSystem) {
            throw new InvalidArgumentException('Grading system must be set before performing calculations.');
        }
    }

    public function calculateGrade(float $score): ?GradeScale
    {
        $this->ensureGradingSystem();

        return $this->gradingSystem->gradeScales
            ->first(fn (GradeScale $scale) => $scale->containsScore($score));
    }

    public function calculateGPA(array $scores): float
    {
        $this->ensureGradingSystem();

        if (empty($scores)) {
            return 0.0;
        }

        $totalPoints = 0;
        $count = 0;

        foreach ($scores as $score) {
            $grade = $this->calculateGrade($score);
            if ($grade && $grade->gpa_points !== null) {
                $totalPoints += $grade->gpa_points;
                $count++;
            }
        }

        return $count > 0 ? round($totalPoints / $count, 2) : 0.0;
    }

    public function calculateAverage(array $scores): float
    {
        if (empty($scores)) {
            return 0.0;
        }

        return round(array_sum($scores) / count($scores), 2);
    }

    public function isPassing(float $score): bool
    {
        $this->ensureGradingSystem();

        $grade = $this->calculateGrade($score);
        return $grade ? $grade->is_passing : false;
    }

    public function isSubjectPassing(string $subjectCode, float $score): bool
    {
        $this->ensureGradingSystem();

        $isPrioritySubject = $this->gradingSystem->isPrioritySubject($subjectCode);
        $grade = $this->calculateGrade($score);

        if (!$grade) {
            return false;
        }

        if ($isPrioritySubject) {
            $priorityPassMark = $this->gradingSystem->settings['priority_pass_mark'] 
                ?? $this->gradingSystem->pass_mark;
            return $score >= $priorityPassMark && $grade->is_passing;
        }

        return $grade->is_passing;
    }

    public function calculateOverallResult(array $subjectScores): array
    {
        $this->ensureGradingSystem();

        $results = [];
        $totalScore = 0;
        $passingCount = 0;
        $failingCount = 0;
        $prioritySubjectsPassed = 0;
        $prioritySubjectsTotal = 0;

        foreach ($subjectScores as $subjectCode => $score) {
            $grade = $this->calculateGrade($score);
            $isPassing = $this->isSubjectPassing($subjectCode, $score);
            $isPriority = $this->gradingSystem->isPrioritySubject($subjectCode);

            $results['subjects'][$subjectCode] = [
                'score' => $score,
                'grade' => $grade?->grade,
                'grade_label' => $grade?->grade_label,
                'gpa_points' => $grade?->gpa_points,
                'points' => $grade?->points,
                'remark' => $grade?->remark,
                'is_passing' => $isPassing,
                'is_priority' => $isPriority,
            ];

            $totalScore += $score;

            if ($isPassing) {
                $passingCount++;
            } else {
                $failingCount++;
            }

            if ($isPriority) {
                $prioritySubjectsTotal++;
                if ($isPassing) {
                    $prioritySubjectsPassed++;
                }
            }
        }

        $subjectCount = count($subjectScores);
        $average = $subjectCount > 0 ? round($totalScore / $subjectCount, 2) : 0;
        $overallGrade = $this->calculateGrade($average);

        $results['summary'] = [
            'total_subjects' => $subjectCount,
            'subjects_passed' => $passingCount,
            'subjects_failed' => $failingCount,
            'priority_subjects_passed' => $prioritySubjectsPassed,
            'priority_subjects_total' => $prioritySubjectsTotal,
            'total_score' => $totalScore,
            'average_score' => $average,
            'overall_grade' => $overallGrade?->grade,
            'overall_grade_label' => $overallGrade?->grade_label,
            'gpa' => $this->calculateGPA(array_values($subjectScores)),
            'meets_pass_criteria' => $this->meetsPromotionCriteria($subjectScores),
            'meets_certification' => $this->meetsCertificationCriteria($subjectScores),
        ];

        return $results;
    }

    public function meetsPromotionCriteria(array $subjectScores): bool
    {
        $this->ensureGradingSystem();

        $passingCount = 0;
        $prioritySubjectsPassed = true;

        foreach ($subjectScores as $subjectCode => $score) {
            if ($this->isSubjectPassing($subjectCode, $score)) {
                $passingCount++;
            } elseif ($this->gradingSystem->isPrioritySubject($subjectCode)) {
                $prioritySubjectsPassed = false;
            }
        }

        $minSubjects = $this->gradingSystem->min_subjects_to_pass;
        $requirePriorityPass = $this->gradingSystem->settings['require_priority_pass'] ?? true;

        if ($requirePriorityPass && !$prioritySubjectsPassed) {
            return false;
        }

        return $passingCount >= $minSubjects;
    }

    public function meetsCertificationCriteria(array $subjectScores): bool
    {
        $this->ensureGradingSystem();

        $rules = $this->gradingSystem->certification_rules ?? [];

        if (empty($rules)) {
            return $this->meetsPromotionCriteria($subjectScores);
        }

        $passingCount = 0;
        $prioritySubjectsPassed = 0;
        $prioritySubjectsRequired = $rules['priority_subjects_required'] ?? [];

        foreach ($subjectScores as $subjectCode => $score) {
            if ($this->isSubjectPassing($subjectCode, $score)) {
                $passingCount++;

                if (in_array($subjectCode, $prioritySubjectsRequired)) {
                    $prioritySubjectsPassed++;
                }
            }
        }

        $minSubjects = $rules['min_subjects'] ?? $this->gradingSystem->min_subjects_to_pass;
        $minPrioritySubjects = $rules['min_priority_subjects'] ?? count($prioritySubjectsRequired);

        return $passingCount >= $minSubjects && $prioritySubjectsPassed >= $minPrioritySubjects;
    }

    public function getGradeRemark(float $score): string
    {
        $grade = $this->calculateGrade($score);
        return $grade?->remark ?? 'N/A';
    }

    public function getRanking(array $scores): array
    {
        arsort($scores);

        $ranked = [];
        $rank = 1;
        $previousScore = null;
        $sameRankCount = 0;

        foreach ($scores as $identifier => $score) {
            if ($previousScore !== null && $score < $previousScore) {
                $rank += $sameRankCount;
                $sameRankCount = 1;
            } else {
                $sameRankCount++;
            }

            $ranked[$identifier] = [
                'score' => $score,
                'rank' => $rank,
                'grade' => $this->calculateGrade($score)?->grade,
            ];

            $previousScore = $score;
        }

        return $ranked;
    }

    public function getGradingSystem(): ?GradingSystem
    {
        return $this->gradingSystem;
    }
}
