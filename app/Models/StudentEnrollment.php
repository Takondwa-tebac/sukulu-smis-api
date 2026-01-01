<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentEnrollment extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PROMOTED = 'promoted';
    public const STATUS_REPEATED = 'repeated';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_GRADUATED = 'graduated';

    protected $fillable = [
        'school_id',
        'student_id',
        'academic_year_id',
        'class_id',
        'stream_id',
        'roll_number',
        'enrollment_date',
        'withdrawal_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'withdrawal_date' => 'date',
        'roll_number' => 'integer',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function studentSubjects(): HasMany
    {
        return $this->hasMany(StudentSubject::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForAcademicYear($query, string $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeForClass($query, string $classId)
    {
        return $query->where('class_id', $classId);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_PROMOTED,
            self::STATUS_REPEATED,
            self::STATUS_TRANSFERRED,
            self::STATUS_WITHDRAWN,
            self::STATUS_GRADUATED,
        ];
    }
}
