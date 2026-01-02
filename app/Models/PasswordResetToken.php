<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PasswordResetToken extends Model
{
    use HasFactory, BaseModel, SoftDeletes;

    protected $fillable = [
        'email',
        'token',
        'created_at',
        'expires_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Check if the token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the token is valid
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Delete expired tokens (hard delete)
     */
    public static function deleteExpired(): int
    {
        return static::where('expires_at', '<', now())->forceDelete();
    }

    /**
     * Delete all tokens for an email (hard delete)
     */
    public static function deleteForEmail(string $email): int
    {
        return static::where('email', $email)->forceDelete();
    }
}
