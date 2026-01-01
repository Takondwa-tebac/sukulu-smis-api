<?php

namespace App\Services\Grading;

use App\Models\GradingSystem;
use App\Models\School;
use App\Models\SchoolGradingConfig;

class GradingEngineFactory
{
    public static function make(?GradingSystem $gradingSystem = null): GradingEngine
    {
        return new GradingEngine($gradingSystem);
    }

    public static function forSchool(School $school, string $level = 'all'): GradingEngine
    {
        $config = SchoolGradingConfig::where('school_id', $school->id)
            ->where('is_active', true)
            ->forLevel($level)
            ->with('gradingSystem.gradeScales')
            ->first();

        if ($config) {
            return new GradingEngine($config->gradingSystem);
        }

        $defaultSystem = self::getDefaultSystemForSchoolType($school->type);
        return new GradingEngine($defaultSystem);
    }

    public static function forSchoolType(string $schoolType): GradingEngine
    {
        $gradingSystem = self::getDefaultSystemForSchoolType($schoolType);
        return new GradingEngine($gradingSystem);
    }

    protected static function getDefaultSystemForSchoolType(string $schoolType): ?GradingSystem
    {
        $typeMapping = [
            School::TYPE_PRIMARY => GradingSystem::TYPE_PRIMARY,
            School::TYPE_SECONDARY => GradingSystem::TYPE_SECONDARY_MSCE,
            School::TYPE_INTERNATIONAL => GradingSystem::TYPE_INTERNATIONAL,
        ];

        $gradingType = $typeMapping[$schoolType] ?? GradingSystem::TYPE_PRIMARY;

        return GradingSystem::systemDefaults()
            ->ofType($gradingType)
            ->active()
            ->with('gradeScales')
            ->first();
    }
}
