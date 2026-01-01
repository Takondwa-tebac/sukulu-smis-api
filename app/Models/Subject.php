<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const CATEGORY_CORE = 'core';
    public const CATEGORY_ELECTIVE = 'elective';
    public const CATEGORY_OPTIONAL = 'optional';
    public const CATEGORY_EXTRA_CURRICULAR = 'extra_curricular';

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'short_name',
        'description',
        'category',
        'is_priority',
        'is_active',
        'applicable_levels',
        'sort_order',
    ];

    protected $casts = [
        'is_priority' => 'boolean',
        'is_active' => 'boolean',
        'applicable_levels' => 'array',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'category' => self::CATEGORY_CORE,
        'is_priority' => false,
        'is_active' => true,
        'sort_order' => 0,
    ];

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_subjects');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePriority($query)
    {
        return $query->where('is_priority', true);
    }

    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_CORE,
            self::CATEGORY_ELECTIVE,
            self::CATEGORY_OPTIONAL,
            self::CATEGORY_EXTRA_CURRICULAR,
        ];
    }
}
