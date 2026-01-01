<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class School extends Model implements HasMedia
{
    use HasFactory, BaseModel, InteractsWithMedia;

    public const TYPE_PRIMARY = 'primary';
    public const TYPE_SECONDARY = 'secondary';
    public const TYPE_INTERNATIONAL = 'international';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'name',
        'code',
        'type',
        'address',
        'city',
        'region',
        'country',
        'postal_code',
        'phone',
        'email',
        'website',
        'motto',
        'established_year',
        'registration_number',
        'status',
        'subscription_plan',
        'subscription_expires_at',
        'enabled_modules',
        'settings',
        'metadata',
    ];

    protected $casts = [
        'enabled_modules' => 'array',
        'settings' => 'array',
        'metadata' => 'array',
        'subscription_expires_at' => 'datetime',
        'established_year' => 'integer',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'type' => self::TYPE_PRIMARY,
        'country' => 'Malawi',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $school) {
            if (empty($school->code) && !empty($school->name)) {
                $base = Str::slug($school->name);
                $suffix = substr((string) Str::uuid(), 0, 8);
                $school->code = $base . '-' . $suffix;
            }

            if (empty($school->enabled_modules)) {
                $school->enabled_modules = self::getDefaultModules();
            }
        });
    }

    public static function getDefaultModules(): array
    {
        return [
            'academic_structure' => true,
            'students' => true,
            'grading' => true,
            'exams' => true,
            'reports' => true,
            'attendance' => true,
            'admissions' => false,
            'fees' => false,
            'timetables' => false,
            'discipline' => false,
            'notifications' => true,
        ];
    }

    public static function getSchoolTypes(): array
    {
        return [
            self::TYPE_PRIMARY,
            self::TYPE_SECONDARY,
            self::TYPE_INTERNATIONAL,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile();

        $this->addMediaCollection('banner')
            ->singleFile();

        $this->addMediaCollection('documents');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function roles()
    {
        return $this->hasMany(Role::class, 'school_id');
    }

    public function isModuleEnabled(string $module): bool
    {
        return $this->enabled_modules[$module] ?? false;
    }

    public function enableModule(string $module): void
    {
        $modules = $this->enabled_modules ?? [];
        $modules[$module] = true;
        $this->enabled_modules = $modules;
        $this->save();
    }

    public function disableModule(string $module): void
    {
        $modules = $this->enabled_modules ?? [];
        $modules[$module] = false;
        $this->enabled_modules = $modules;
        $this->save();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function hasValidSubscription(): bool
    {
        return $this->subscription_expires_at === null 
            || $this->subscription_expires_at->isFuture();
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
