<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Guardian extends Model implements HasMedia
{
    use HasFactory, BaseModel, BelongsToTenant, InteractsWithMedia;

    public const RELATIONSHIP_FATHER = 'father';
    public const RELATIONSHIP_MOTHER = 'mother';
    public const RELATIONSHIP_GUARDIAN = 'guardian';
    public const RELATIONSHIP_GRANDPARENT = 'grandparent';
    public const RELATIONSHIP_SIBLING = 'sibling';
    public const RELATIONSHIP_UNCLE = 'uncle';
    public const RELATIONSHIP_AUNT = 'aunt';
    public const RELATIONSHIP_OTHER = 'other';

    protected $fillable = [
        'school_id',
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'title',
        'gender',
        'national_id',
        'date_of_birth',
        'email',
        'phone_primary',
        'phone_secondary',
        'occupation',
        'employer',
        'address',
        'city',
        'region',
        'country',
        'relationship_type',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'country' => 'Malawi',
        'relationship_type' => self::RELATIONSHIP_GUARDIAN,
        'is_active' => true,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_guardian')
            ->withPivot(['relationship', 'is_primary', 'is_emergency_contact', 'can_pickup', 'receives_reports', 'receives_invoices'])
            ->withTimestamps();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
        $this->addMediaCollection('documents');
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->title,
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]);
        return implode(' ', $parts);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getRelationshipTypes(): array
    {
        return [
            self::RELATIONSHIP_FATHER,
            self::RELATIONSHIP_MOTHER,
            self::RELATIONSHIP_GUARDIAN,
            self::RELATIONSHIP_GRANDPARENT,
            self::RELATIONSHIP_SIBLING,
            self::RELATIONSHIP_UNCLE,
            self::RELATIONSHIP_AUNT,
            self::RELATIONSHIP_OTHER,
        ];
    }
}
