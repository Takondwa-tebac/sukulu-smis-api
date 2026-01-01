<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisciplineIncidentAction extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'incident_id',
        'action_id',
        'start_date',
        'end_date',
        'notes',
        'status',
        'completed_at',
        'assigned_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(DisciplineIncident::class, 'incident_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(DisciplineAction::class, 'action_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function approve(string $approvedById): void
    {
        $this->update([
            'approved_by' => $approvedById,
            'approved_at' => now(),
            'status' => self::STATUS_IN_PROGRESS,
        ]);
    }
}
