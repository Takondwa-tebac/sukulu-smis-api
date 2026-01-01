<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSubject extends Model
{
    use HasFactory, BaseModel;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_MARKS_ENTERED = 'marks_entered';
    public const STATUS_MODERATED = 'moderated';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'exam_id',
        'class_subject_id',
        'exam_date',
        'start_time',
        'duration_minutes',
        'max_score',
        'venue',
        'status',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'start_time' => 'datetime:H:i',
        'duration_minutes' => 'integer',
        'max_score' => 'decimal:2',
    ];

    protected $attributes = [
        'max_score' => 100,
        'status' => self::STATUS_PENDING,
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    public function studentMarks(): HasMany
    {
        return $this->hasMany(StudentMark::class);
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function canEnterMarks(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED]);
    }

    public function canModerate(): bool
    {
        return $this->status === self::STATUS_MARKS_ENTERED;
    }

    public function canApprove(): bool
    {
        return $this->status === self::STATUS_MODERATED;
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_MARKS_ENTERED,
            self::STATUS_MODERATED,
            self::STATUS_APPROVED,
            self::STATUS_LOCKED,
        ];
    }
}
