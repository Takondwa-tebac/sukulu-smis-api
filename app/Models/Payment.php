<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    public const METHOD_CASH = 'cash';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_MOBILE_MONEY = 'mobile_money';
    public const METHOD_CHEQUE = 'cheque';
    public const METHOD_CARD = 'card';
    public const METHOD_OTHER = 'other';

    protected $fillable = [
        'school_id',
        'student_id',
        'student_invoice_id',
        'payment_number',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'bank_name',
        'cheque_number',
        'mobile_money_number',
        'transaction_id',
        'status',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => self::STATUS_COMPLETED,
        'payment_method' => self::METHOD_CASH,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber($payment->school_id);
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function allocateToInvoice(StudentInvoice $invoice, float $amount): PaymentAllocation
    {
        $allocation = PaymentAllocation::create([
            'payment_id' => $this->id,
            'student_invoice_id' => $invoice->id,
            'amount' => $amount,
        ]);

        $invoice->recalculateTotals();

        return $allocation;
    }

    public function getUnallocatedAmount(): float
    {
        return $this->amount - $this->allocations()->sum('amount');
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_REFUNDED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function getPaymentMethods(): array
    {
        return [
            self::METHOD_CASH,
            self::METHOD_BANK_TRANSFER,
            self::METHOD_MOBILE_MONEY,
            self::METHOD_CHEQUE,
            self::METHOD_CARD,
            self::METHOD_OTHER,
        ];
    }

    public static function generatePaymentNumber(string $schoolId): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $count = static::where('school_id', $schoolId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        return sprintf('PAY-%s%s-%05d', $year, $month, $count);
    }
}
