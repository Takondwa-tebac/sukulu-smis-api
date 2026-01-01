<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContinuousAssessment extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_MARKS_ENTERED = 'marks_entered';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'term_id',
        'class_subject_id',
        'assessment_component_id',
        'title',
        'assessment_date',
        'max_score',
        'status',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'max_score' => 'decimal:2',
    ];

    protected $attributes = [
        'max_score' => 100,
        'status' => self::STATUS_DRAFT,
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    public function assessmentComponent(): BelongsTo
    {
        return $this->belongsTo(AssessmentComponent::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(ContinuousAssessmentMark::class);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_MARKS_ENTERED,
            self::STATUS_SUBMITTED,
            self::STATUS_APPROVED,
            self::STATUS_LOCKED,
        ];
    }
}
