<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisciplineIncident extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const STATUS_REPORTED = 'reported';
    public const STATUS_UNDER_INVESTIGATION = 'under_investigation';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';
    public const STATUS_APPEALED = 'appealed';

    protected $fillable = [
        'school_id',
        'student_id',
        'category_id',
        'academic_year_id',
        'term_id',
        'incident_number',
        'incident_date',
        'incident_time',
        'location',
        'description',
        'witnesses',
        'points_assigned',
        'status',
        'resolution_notes',
        'resolved_at',
        'resolved_by',
        'reported_by',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'incident_time' => 'datetime:H:i',
        'points_assigned' => 'integer',
        'resolved_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $incident) {
            if (empty($incident->incident_number)) {
                $incident->incident_number = self::generateIncidentNumber($incident->school_id);
            }
        });
    }

    public static function generateIncidentNumber(string $schoolId): string
    {
        $year = now()->format('Y');
        $count = self::where('school_id', $schoolId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('INC-%s-%04d', $year, $count);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DisciplineCategory::class, 'category_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(DisciplineIncidentAction::class, 'incident_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(DisciplineNotification::class, 'incident_id');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForStudent($query, string $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('incident_date', [$startDate, $endDate]);
    }

    public function resolve(string $notes, string $resolvedById): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
            'resolved_by' => $resolvedById,
        ]);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_REPORTED,
            self::STATUS_UNDER_INVESTIGATION,
            self::STATUS_RESOLVED,
            self::STATUS_DISMISSED,
            self::STATUS_APPEALED,
        ];
    }
}
