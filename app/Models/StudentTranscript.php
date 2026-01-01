<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentTranscript extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ISSUED = 'issued';

    public const TYPE_PARTIAL = 'partial';
    public const TYPE_COMPLETE = 'complete';
    public const TYPE_OFFICIAL = 'official';

    protected $fillable = [
        'school_id',
        'student_id',
        'transcript_number',
        'issue_date',
        'type',
        'cumulative_gpa',
        'graduation_status',
        'graduation_date',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'issued_by',
        'issued_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'graduation_date' => 'date',
        'cumulative_gpa' => 'decimal:2',
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'type' => self::TYPE_COMPLETE,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $transcript) {
            if (empty($transcript->transcript_number)) {
                $transcript->transcript_number = self::generateTranscriptNumber($transcript->school_id);
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function issuedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(TranscriptRecord::class);
    }

    public static function generateTranscriptNumber(string $schoolId): string
    {
        $year = now()->format('Y');
        $count = static::where('school_id', $schoolId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('TR-%s-%05d', $year, $count);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_GENERATED,
            self::STATUS_APPROVED,
            self::STATUS_ISSUED,
        ];
    }
}
