<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid;

class StudentSubject extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'student_enrollment_id',
        'class_subject_id',
        'is_active',
        'dropped_date',
        'drop_reason',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'dropped_date' => 'date',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
