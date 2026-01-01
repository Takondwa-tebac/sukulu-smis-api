<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Student extends Model implements HasMedia
{
    use HasFactory, BaseModel, BelongsToTenant, InteractsWithMedia;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_GRADUATED = 'graduated';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_EXPELLED = 'expelled';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_DECEASED = 'deceased';

    protected $fillable = [
        'school_id',
        'admission_number',
        'student_id_number',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'nationality',
        'national_id',
        'birth_certificate_number',
        'address',
        'city',
        'region',
        'country',
        'email',
        'phone',
        'blood_group',
        'medical_conditions',
        'allergies',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'previous_school',
        'previous_school_address',
        'admission_date',
        'status',
        'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'country' => 'Malawi',
        'nationality' => 'Malawian',
        'status' => self::STATUS_ACTIVE,
    ];

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_guardian')
            ->withPivot(['relationship', 'is_primary', 'is_emergency_contact', 'can_pickup', 'receives_reports', 'receives_invoices'])
            ->withTimestamps();
    }

    public function primaryGuardian(): BelongsToMany
    {
        return $this->guardians()->wherePivot('is_primary', true);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class)->orderByDesc('created_at');
    }

    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class)
            ->where('status', 'active')
            ->latestOfMany();
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(StudentPromotion::class)->orderByDesc('promoted_at');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
        $this->addMediaCollection('birth_certificate')->singleFile();
        $this->addMediaCollection('documents');
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]);
        return implode(' ', $parts);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_GRADUATED,
            self::STATUS_TRANSFERRED,
            self::STATUS_EXPELLED,
            self::STATUS_WITHDRAWN,
            self::STATUS_DECEASED,
        ];
    }

    public static function generateAdmissionNumber(School $school): string
    {
        $year = now()->format('Y');
        $prefix = strtoupper(substr($school->code, 0, 3));
        $count = static::where('school_id', $school->id)
            ->whereYear('created_at', $year)
            ->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $year, $count);
    }
}
