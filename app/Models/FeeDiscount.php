<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeDiscount extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'fee_category_id',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'discount_type' => self::TYPE_PERCENTAGE,
        'is_active' => true,
    ];

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function studentDiscounts(): HasMany
    {
        return $this->hasMany(StudentDiscount::class);
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->discount_type === self::TYPE_PERCENTAGE) {
            return $amount * ($this->discount_value / 100);
        }

        return min($this->discount_value, $amount);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
