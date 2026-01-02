<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantInvoiceItem extends Model
{
    use HasFactory, BaseModel;

    protected $fillable = [
        'tenant_invoice_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    protected $attributes = [
        'quantity' => 1,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $item) {
            $item->amount = $item->quantity * $item->unit_price;
        });

        static::saved(function (self $item) {
            $item->invoice->recalculateTotals();
        });

        static::deleted(function (self $item) {
            $item->invoice->recalculateTotals();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(TenantInvoice::class, 'tenant_invoice_id');
    }
}
