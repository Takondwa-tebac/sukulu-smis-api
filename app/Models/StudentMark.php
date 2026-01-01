<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentMark extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_MODERATED = 'moderated';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'school_id',
        'exam_subject_id',
        'student_id',
        'score',
        'grade',
        'remarks',
        'is_absent',
        'absent_reason',
        'status',
        'entered_by',
        'entered_at',
        'moderated_by',
        'moderated_at',
        'original_score',
        'moderation_reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'original_score' => 'decimal:2',
        'is_absent' => 'boolean',
        'entered_at' => 'datetime',
        'moderated_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected $attributes = [
        'is_absent' => false,
        'status' => self::STATUS_DRAFT,
    ];

    public function examSubject(): BelongsTo
    {
        return $this->belongsTo(ExamSubject::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enteredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function moderatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function moderationLogs(): HasMany
    {
        return $this->hasMany(MarkModerationLog::class);
    }

    public function moderate(float $newScore, string $reason, string $userId): void
    {
        MarkModerationLog::create([
            'student_mark_id' => $this->id,
            'original_score' => $this->score,
            'moderated_score' => $newScore,
            'reason' => $reason,
            'moderated_by' => $userId,
        ]);

        $this->update([
            'original_score' => $this->score,
            'score' => $newScore,
            'moderation_reason' => $reason,
            'moderated_by' => $userId,
            'moderated_at' => now(),
            'status' => self::STATUS_MODERATED,
        ]);
    }

    public function approve(string $userId): void
    {
        $this->update([
            'approved_by' => $userId,
            'approved_at' => now(),
            'status' => self::STATUS_APPROVED,
        ]);
    }

    public function lock(): void
    {
        $this->update(['status' => self::STATUS_LOCKED]);
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_MODERATED,
            self::STATUS_APPROVED,
            self::STATUS_LOCKED,
        ];
    }
}
