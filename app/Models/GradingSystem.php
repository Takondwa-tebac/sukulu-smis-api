<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingSystem extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const TYPE_PRIMARY = 'primary';
    public const TYPE_SECONDARY_JCE = 'secondary_jce';
    public const TYPE_SECONDARY_MSCE = 'secondary_msce';
    public const TYPE_INTERNATIONAL = 'international';

    public const SCALE_LETTER = 'letter';
    public const SCALE_NUMERIC = 'numeric';
    public const SCALE_PERCENTAGE = 'percentage';
    public const SCALE_GPA = 'gpa';
    public const SCALE_POINTS = 'points';

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'type',
        'scale_type',
        'min_score',
        'max_score',
        'pass_mark',
        'min_subjects_to_pass',
        'priority_subjects',
        'certification_rules',
        'progression_rules',
        'settings',
        'version',
        'is_system_default',
        'is_locked',
        'is_active',
    ];

    protected $casts = [
        'priority_subjects' => 'array',
        'certification_rules' => 'array',
        'progression_rules' => 'array',
        'settings' => 'array',
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'pass_mark' => 'decimal:2',
        'min_subjects_to_pass' => 'integer',
        'version' => 'integer',
        'is_system_default' => 'boolean',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'min_score' => 0,
        'max_score' => 100,
        'pass_mark' => 50,
        'min_subjects_to_pass' => 6,
        'version' => 1,
        'is_system_default' => false,
        'is_locked' => false,
        'is_active' => true,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function (self $gradingSystem) {
            if ($gradingSystem->isDirty() && !$gradingSystem->isDirty('is_active')) {
                $gradingSystem->createHistorySnapshot('Updated');
            }
        });
    }

    public function gradeScales(): HasMany
    {
        return $this->hasMany(GradeScale::class)->orderBy('sort_order');
    }

    public function schoolConfigs(): HasMany
    {
        return $this->hasMany(SchoolGradingConfig::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(GradingSystemHistory::class)->orderByDesc('version');
    }

    public function createHistorySnapshot(string $reason = null): GradingSystemHistory
    {
        return $this->histories()->create([
            'version' => $this->version,
            'snapshot' => $this->toArray(),
            'change_reason' => $reason,
            'changed_by' => auth()->id(),
        ]);
    }

    public function incrementVersion(): void
    {
        $this->increment('version');
    }

    public function calculateGrade(float $score): ?GradeScale
    {
        return $this->gradeScales()
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();
    }

    public function isPassing(float $score): bool
    {
        $grade = $this->calculateGrade($score);
        return $grade ? $grade->is_passing : false;
    }

    public function getPrioritySubjects(): array
    {
        return $this->priority_subjects ?? [];
    }

    public function isPrioritySubject(string $subjectCode): bool
    {
        return in_array($subjectCode, $this->getPrioritySubjects());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystemDefaults($query)
    {
        return $query->where('is_system_default', true)->whereNull('school_id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_PRIMARY,
            self::TYPE_SECONDARY_JCE,
            self::TYPE_SECONDARY_MSCE,
            self::TYPE_INTERNATIONAL,
        ];
    }

    public static function getScaleTypes(): array
    {
        return [
            self::SCALE_LETTER,
            self::SCALE_NUMERIC,
            self::SCALE_PERCENTAGE,
            self::SCALE_GPA,
            self::SCALE_POINTS,
        ];
    }
}
