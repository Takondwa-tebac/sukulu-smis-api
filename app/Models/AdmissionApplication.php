<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AdmissionApplication extends Model implements HasMedia
{
    use HasFactory, BaseModel, BelongsToTenant, InteractsWithMedia;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_DOCUMENTS_PENDING = 'documents_pending';
    public const STATUS_INTERVIEW_SCHEDULED = 'interview_scheduled';
    public const STATUS_INTERVIEWED = 'interviewed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WAITLISTED = 'waitlisted';
    public const STATUS_ENROLLED = 'enrolled';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'school_id',
        'admission_period_id',
        'application_number',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'nationality',
        'birth_certificate_number',
        'address',
        'city',
        'region',
        'country',
        'previous_school',
        'previous_school_address',
        'previous_class',
        'previous_average',
        'applied_class_id',
        'preferred_stream_id',
        'guardian_first_name',
        'guardian_last_name',
        'guardian_relationship',
        'guardian_phone',
        'guardian_email',
        'guardian_occupation',
        'guardian_address',
        'status',
        'status_reason',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'decision_at',
        'decision_by',
        'interview_date',
        'interview_notes',
        'interview_score',
        'entrance_exam_score',
        'entrance_exam_date',
        'fee_paid',
        'fee_paid_at',
        'payment_reference',
        'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'previous_average' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'decision_at' => 'datetime',
        'interview_date' => 'datetime',
        'interview_score' => 'integer',
        'entrance_exam_score' => 'decimal:2',
        'entrance_exam_date' => 'date',
        'fee_paid' => 'boolean',
        'fee_paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'country' => 'Malawi',
        'nationality' => 'Malawian',
        'fee_paid' => false,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $application) {
            if (empty($application->application_number)) {
                $application->application_number = self::generateApplicationNumber($application->school_id);
            }
        });
    }

    public function admissionPeriod(): BelongsTo
    {
        return $this->belongsTo(AdmissionPeriod::class);
    }

    public function appliedClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'applied_class_id');
    }

    public function preferredStream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'preferred_stream_id');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function decisionByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->orderByDesc('created_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ApplicationComment::class)->orderByDesc('created_at');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
        $this->addMediaCollection('documents');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getGuardianFullNameAttribute(): string
    {
        return trim("{$this->guardian_first_name} {$this->guardian_last_name}");
    }

    public function updateStatus(string $newStatus, ?string $reason = null, ?string $userId = null): void
    {
        $oldStatus = $this->status;

        $this->update([
            'status' => $newStatus,
            'status_reason' => $reason,
        ]);

        ApplicationStatusHistory::create([
            'admission_application_id' => $this->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $reason,
            'changed_by' => $userId,
        ]);
    }

    public function submit(): void
    {
        $this->updateStatus(self::STATUS_SUBMITTED);
        $this->update(['submitted_at' => now()]);
    }

    public function approve(?string $userId = null): void
    {
        $this->updateStatus(self::STATUS_APPROVED, null, $userId);
        $this->update([
            'decision_at' => now(),
            'decision_by' => $userId,
        ]);
    }

    public function reject(string $reason, ?string $userId = null): void
    {
        $this->updateStatus(self::STATUS_REJECTED, $reason, $userId);
        $this->update([
            'decision_at' => now(),
            'decision_by' => $userId,
        ]);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_DOCUMENTS_PENDING]);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_DOCUMENTS_PENDING,
        ]);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_DOCUMENTS_PENDING,
            self::STATUS_INTERVIEW_SCHEDULED,
            self::STATUS_INTERVIEWED,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_WAITLISTED,
            self::STATUS_ENROLLED,
            self::STATUS_WITHDRAWN,
            self::STATUS_EXPIRED,
        ];
    }

    public static function generateApplicationNumber(string $schoolId): string
    {
        $year = now()->format('Y');
        $count = static::where('school_id', $schoolId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('APP-%s-%05d', $year, $count);
    }
}
