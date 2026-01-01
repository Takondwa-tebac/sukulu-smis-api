<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentComponent extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'weight',
        'max_score',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'weight' => 100,
        'max_score' => 100,
        'is_active' => true,
    ];

    public function continuousAssessments(): HasMany
    {
        return $this->hasMany(ContinuousAssessment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
