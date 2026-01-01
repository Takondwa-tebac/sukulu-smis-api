<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranscriptRecord extends Model
{
    use HasUuid;

    protected $fillable = [
        'student_transcript_id',
        'academic_year_id',
        'term_id',
        'class_id',
        'subject_id',
        'score',
        'grade',
        'gpa_points',
        'credit_hours',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'gpa_points' => 'decimal:2',
        'credit_hours' => 'integer',
    ];

    public function transcript(): BelongsTo
    {
        return $this->belongsTo(StudentTranscript::class, 'student_transcript_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
