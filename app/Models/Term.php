<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Term extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const STATUS_PLANNING = 'planning';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'name',
        'term_number',
        'start_date',
        'end_date',
        'is_current',
        'status',
        'settings',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'term_number' => 'integer',
        'is_current' => 'boolean',
        'settings' => 'array',
    ];

    protected $attributes = [
        'is_current' => false,
        'status' => self::STATUS_PLANNING,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $term) {
            if ($term->is_current && $term->isDirty('is_current')) {
                static::where('school_id', $term->school_id)
                    ->where('id', '!=', $term->id)
                    ->update(['is_current' => false]);
            }
        });
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function setAsCurrent(): void
    {
        $this->update(['is_current' => true, 'status' => self::STATUS_ACTIVE]);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForAcademicYear($query, string $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PLANNING,
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
        ];
    }
}
