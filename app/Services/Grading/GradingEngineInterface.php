<?php

namespace App\Services\Grading;

use App\Models\GradeScale;
use App\Models\GradingSystem;

interface GradingEngineInterface
{
    public function setGradingSystem(GradingSystem $gradingSystem): self;

    public function calculateGrade(float $score): ?GradeScale;

    public function calculateGPA(array $scores): float;

    public function calculateAverage(array $scores): float;

    public function isPassing(float $score): bool;

    public function isSubjectPassing(string $subjectCode, float $score): bool;

    public function calculateOverallResult(array $subjectScores): array;

    public function meetsPromotionCriteria(array $subjectScores): bool;

    public function meetsCertificationCriteria(array $subjectScores): bool;

    public function getGradeRemark(float $score): string;

    public function getRanking(array $scores): array;
}
