<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const STATUS_PLANNING = 'planning';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'school_id',
        'name',
        'start_date',
        'end_date',
        'is_current',
        'status',
        'settings',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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

        static::saving(function (self $academicYear) {
            if ($academicYear->is_current && $academicYear->isDirty('is_current')) {
                static::where('school_id', $academicYear->school_id)
                    ->where('id', '!=', $academicYear->id)
                    ->update(['is_current' => false]);
            }
        });
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class)->orderBy('term_number');
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
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

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PLANNING,
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_ARCHIVED,
        ];
    }
}
