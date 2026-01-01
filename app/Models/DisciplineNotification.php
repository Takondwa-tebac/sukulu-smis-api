<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplineNotification extends Model
{
    use HasFactory;

    public const METHOD_EMAIL = 'email';
    public const METHOD_SMS = 'sms';
    public const METHOD_LETTER = 'letter';
    public const METHOD_MEETING = 'meeting';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'incident_id',
        'guardian_id',
        'method',
        'sent_at',
        'acknowledged_at',
        'notes',
        'sent_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime',
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

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function markAsSent(string $sentById): void
    {
        $this->update([
            'sent_at' => now(),
            'sent_by' => $sentById,
        ]);
    }

    public function markAsAcknowledged(): void
    {
        $this->update([
            'acknowledged_at' => now(),
        ]);
    }
}
