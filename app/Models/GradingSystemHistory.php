<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingSystemHistory extends Model
{
    use HasUuid;

    protected $fillable = [
        'grading_system_id',
        'version',
        'snapshot',
        'change_reason',
        'changed_by',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'version' => 'integer',
    ];

    public function gradingSystem(): BelongsTo
    {
        return $this->belongsTo(GradingSystem::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
