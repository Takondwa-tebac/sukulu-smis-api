<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeScale extends Model
{
    use HasFactory, BaseModel;

    protected $fillable = [
        'grading_system_id',
        'grade',
        'grade_label',
        'min_score',
        'max_score',
        'gpa_points',
        'points',
        'remark',
        'is_passing',
        'sort_order',
    ];

    protected $casts = [
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'gpa_points' => 'decimal:2',
        'points' => 'integer',
        'is_passing' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'is_passing' => true,
        'sort_order' => 0,
    ];

    public function gradingSystem(): BelongsTo
    {
        return $this->belongsTo(GradingSystem::class);
    }

    public function containsScore(float $score): bool
    {
        return $score >= $this->min_score && $score <= $this->max_score;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopePassing($query)
    {
        return $query->where('is_passing', true);
    }

    public function scopeFailing($query)
    {
        return $query->where('is_passing', false);
    }
}
