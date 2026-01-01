<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'student_invoice_id',
        'fee_structure_id',
        'fee_category_id',
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
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }
}
