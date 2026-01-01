<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolGradingConfig extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const LEVEL_PRIMARY = 'primary';
    public const LEVEL_JCE = 'jce';
    public const LEVEL_MSCE = 'msce';
    public const LEVEL_ALL = 'all';

    protected $fillable = [
        'school_id',
        'grading_system_id',
        'level',
        'custom_priority_subjects',
        'custom_pass_rules',
        'custom_settings',
        'is_active',
    ];

    protected $casts = [
        'custom_priority_subjects' => 'array',
        'custom_pass_rules' => 'array',
        'custom_settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'level' => self::LEVEL_ALL,
        'is_active' => true,
    ];

    public function gradingSystem(): BelongsTo
    {
        return $this->belongsTo(GradingSystem::class);
    }

    public function getPrioritySubjects(): array
    {
        return $this->custom_priority_subjects 
            ?? $this->gradingSystem->priority_subjects 
            ?? [];
    }

    public function getPassRules(): array
    {
        return array_merge(
            $this->gradingSystem->certification_rules ?? [],
            $this->custom_pass_rules ?? []
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLevel($query, string $level)
    {
        return $query->where(function ($q) use ($level) {
            $q->where('level', $level)
              ->orWhere('level', self::LEVEL_ALL);
        });
    }

    public static function getLevels(): array
    {
        return [
            self::LEVEL_PRIMARY,
            self::LEVEL_JCE,
            self::LEVEL_MSCE,
            self::LEVEL_ALL,
        ];
    }
}
