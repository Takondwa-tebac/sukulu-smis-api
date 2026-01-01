<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisciplineCategory extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const SEVERITY_MINOR = 'minor';
    public const SEVERITY_MODERATE = 'moderate';
    public const SEVERITY_MAJOR = 'major';
    public const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'severity',
        'default_points',
        'is_active',
    ];

    protected $casts = [
        'default_points' => 'integer',
        'is_active' => 'boolean',
    ];

    public function incidents(): HasMany
    {
        return $this->hasMany(DisciplineIncident::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public static function getSeverities(): array
    {
        return [
            self::SEVERITY_MINOR,
            self::SEVERITY_MODERATE,
            self::SEVERITY_MAJOR,
            self::SEVERITY_CRITICAL,
        ];
    }
}
