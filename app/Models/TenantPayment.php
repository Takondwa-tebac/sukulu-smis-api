<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPayment extends Model
{
    use HasFactory, BaseModel;

    protected $fillable = [
        'tenant_invoice_id',
        'school_id',
        'amount',
        'currency',
        'payment_method',
        'reference_number',
        'payment_date',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected $attributes = [
        'currency' => 'MWK',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (self $payment) {
            $payment->invoice->recalculateTotals();
        });

        static::deleted(function (self $payment) {
            $payment->invoice->recalculateTotals();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(TenantInvoice::class, 'tenant_invoice_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
