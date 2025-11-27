<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolFactory> */
    use HasFactory , BaseModel;


    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'email',
        'website',
        'metadata',
    ];


    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::bootBaseModel();

        static::creating(function (self $school) {
            if (empty($school->code) && ! empty($school->name)) {
                $base = \Illuminate\Support\Str::slug($school->name);
                $suffix = substr((string) \Illuminate\Support\Str::uuid(), 0, 8);
                $school->code = $base . '-' . $suffix;
            }
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
