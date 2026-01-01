<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPromotion extends Model
{
    use HasFactory, BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    public const TYPE_PROMOTED = 'promoted';
    public const TYPE_REPEATED = 'repeated';
    public const TYPE_SKIPPED = 'skipped';
    public const TYPE_GRADUATED = 'graduated';
    public const TYPE_TRANSFERRED = 'transferred';

    protected $fillable = [
        'school_id',
        'student_id',
        'from_academic_year_id',
        'to_academic_year_id',
        'from_class_id',
        'to_class_id',
        'promotion_type',
        'final_average',
        'final_position',
        'remarks',
        'promoted_by',
        'promoted_at',
    ];

    protected $casts = [
        'final_average' => 'decimal:2',
        'final_position' => 'integer',
        'promoted_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'from_academic_year_id');
    }

    public function toAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'to_academic_year_id');
    }

    public function fromClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'from_class_id');
    }

    public function toClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'to_class_id');
    }

    public function promotedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'promoted_by');
    }

    public static function getPromotionTypes(): array
    {
        return [
            self::TYPE_PROMOTED,
            self::TYPE_REPEATED,
            self::TYPE_SKIPPED,
            self::TYPE_GRADUATED,
            self::TYPE_TRANSFERRED,
        ];
    }
}
