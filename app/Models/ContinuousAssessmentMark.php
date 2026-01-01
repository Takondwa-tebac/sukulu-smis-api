<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContinuousAssessmentMark extends Model
{
    use HasUuid, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'continuous_assessment_id',
        'student_id',
        'score',
        'remarks',
        'is_absent',
        'status',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'is_absent' => 'boolean',
    ];

    protected $attributes = [
        'is_absent' => false,
        'status' => self::STATUS_DRAFT,
    ];

    public function continuousAssessment(): BelongsTo
    {
        return $this->belongsTo(ContinuousAssessment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
