<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkModerationLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'student_mark_id',
        'original_score',
        'moderated_score',
        'reason',
        'moderated_by',
    ];

    protected $casts = [
        'original_score' => 'decimal:2',
        'moderated_score' => 'decimal:2',
    ];

    public function studentMark(): BelongsTo
    {
        return $this->belongsTo(StudentMark::class);
    }

    public function moderatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}
