<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantInvoice extends Model
{
    use HasFactory, BaseModel;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'school_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'discount_reason',
        'total_amount',
        'amount_paid',
        'balance',
        'currency',
        'status',
        'description',
        'notes',
        'sent_at',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    protected $attributes = [
        'subtotal' => 0,
        'discount_amount' => 0,
        'total_amount' => 0,
        'amount_paid' => 0,
        'balance' => 0,
        'currency' => 'MWK',
        'status' => self::STATUS_DRAFT,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TenantInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('amount');
        $this->total_amount = $this->subtotal - $this->discount_amount;
        $this->amount_paid = $this->payments()->sum('amount');
        $this->balance = $this->total_amount - $this->amount_paid;

        if ($this->balance <= 0) {
            $this->status = self::STATUS_PAID;
            $this->paid_at = now();
        } elseif ($this->amount_paid > 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
        } elseif ($this->due_date < now() && !in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PAID])) {
            $this->status = self::STATUS_OVERDUE;
        }

        $this->save();
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue(): bool
    {
        return $this->due_date < now() && $this->balance > 0;
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_PARTIALLY_PAID, self::STATUS_OVERDUE]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereIn('status', [self::STATUS_SENT, self::STATUS_PARTIALLY_PAID]);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
            self::STATUS_PARTIALLY_PAID,
            self::STATUS_PAID,
            self::STATUS_OVERDUE,
            self::STATUS_CANCELLED,
            self::STATUS_VOID,
        ];
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $count = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        return sprintf('TINV-%s%s-%05d', $year, $month, $count);
    }
}
