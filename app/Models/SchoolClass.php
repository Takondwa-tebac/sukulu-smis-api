<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    protected $table = 'classes';

    public const LEVEL_PRIMARY = 'primary';
    public const LEVEL_JCE = 'jce';
    public const LEVEL_MSCE = 'msce';
    public const LEVEL_OTHER = 'other';

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'level',
        'grade_number',
        'capacity',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'grade_number' => 'integer',
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class, 'class_id');
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('grade_number');
    }

    public static function getLevels(): array
    {
        return [
            self::LEVEL_PRIMARY,
            self::LEVEL_JCE,
            self::LEVEL_MSCE,
            self::LEVEL_OTHER,
        ];
    }
}
