<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionPeriod extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'max_applications',
        'application_fee',
        'required_documents',
        'eligible_classes',
        'instructions',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'max_applications' => 'integer',
        'application_fee' => 'decimal:2',
        'required_documents' => 'array',
        'eligible_classes' => 'array',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(AdmissionApplication::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN 
            && now()->between($this->start_date, $this->end_date);
    }

    public function canAcceptApplications(): bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        if ($this->max_applications && $this->applications()->count() >= $this->max_applications) {
            return false;
        }

        return true;
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeForAcademicYear($query, string $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_OPEN,
            self::STATUS_CLOSED,
            self::STATUS_ARCHIVED,
        ];
    }
}
