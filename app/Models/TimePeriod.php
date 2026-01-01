<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimePeriod extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const TYPE_LESSON = 'lesson';
    public const TYPE_BREAK = 'break';
    public const TYPE_ASSEMBLY = 'assembly';
    public const TYPE_LUNCH = 'lunch';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'school_id',
        'name',
        'start_time',
        'end_time',
        'type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'type' => self::TYPE_LESSON,
        'sort_order' => 0,
        'is_active' => true,
    ];

    public function timetableSlots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function getDurationMinutes(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLessons($query)
    {
        return $query->where('type', self::TYPE_LESSON);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('start_time');
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_LESSON,
            self::TYPE_BREAK,
            self::TYPE_ASSEMBLY,
            self::TYPE_LUNCH,
            self::TYPE_OTHER,
        ];
    }
}
