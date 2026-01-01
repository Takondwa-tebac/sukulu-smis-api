<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const SESSION_MORNING = 'morning';
    public const SESSION_AFTERNOON = 'afternoon';
    public const SESSION_FULL_DAY = 'full_day';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_VERIFIED = 'verified';

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'term_id',
        'class_id',
        'stream_id',
        'date',
        'session_type',
        'taken_by',
        'taken_at',
        'notes',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'taken_at' => 'datetime',
    ];

    protected $attributes = [
        'session_type' => self::SESSION_FULL_DAY,
        'status' => self::STATUS_PENDING,
    ];

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

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function takenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function markComplete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'taken_at' => now(),
        ]);
    }

    public function getAttendanceSummary(): array
    {
        $attendances = $this->attendances;
        
        return [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', StudentAttendance::STATUS_PRESENT)->count(),
            'absent' => $attendances->where('status', StudentAttendance::STATUS_ABSENT)->count(),
            'late' => $attendances->where('status', StudentAttendance::STATUS_LATE)->count(),
            'excused' => $attendances->where('status', StudentAttendance::STATUS_EXCUSED)->count(),
        ];
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public static function getSessionTypes(): array
    {
        return [
            self::SESSION_MORNING,
            self::SESSION_AFTERNOON,
            self::SESSION_FULL_DAY,
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_COMPLETED,
            self::STATUS_VERIFIED,
        ];
    }
}
