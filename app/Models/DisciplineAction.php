<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisciplineAction extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const TYPE_WARNING = 'warning';
    public const TYPE_DETENTION = 'detention';
    public const TYPE_SUSPENSION = 'suspension';
    public const TYPE_EXPULSION = 'expulsion';
    public const TYPE_COMMUNITY_SERVICE = 'community_service';
    public const TYPE_COUNSELING = 'counseling';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'type',
        'duration_days',
        'requires_parent_notification',
        'requires_approval',
        'is_active',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'requires_parent_notification' => 'boolean',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function incidentActions(): HasMany
    {
        return $this->hasMany(DisciplineIncidentAction::class, 'action_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_WARNING,
            self::TYPE_DETENTION,
            self::TYPE_SUSPENSION,
            self::TYPE_EXPULSION,
            self::TYPE_COMMUNITY_SERVICE,
            self::TYPE_COUNSELING,
            self::TYPE_OTHER,
        ];
    }
}
