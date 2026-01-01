<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    use HasFactory, BaseModel, BelongsToTenant;

    public const TYPE_SMS = 'sms';
    public const TYPE_EMAIL = 'email';
    public const TYPE_PUSH = 'push';
    public const TYPE_IN_APP = 'in_app';

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'type',
        'subject',
        'body',
        'variables',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected $attributes = [
        'type' => self::TYPE_EMAIL,
        'is_active' => true,
        'is_system' => false,
    ];

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'template_id');
    }

    public function render(array $data): array
    {
        $subject = $this->subject;
        $body = $this->body;

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject ?? '');
            $body = str_replace($placeholder, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_SMS,
            self::TYPE_EMAIL,
            self::TYPE_PUSH,
            self::TYPE_IN_APP,
        ];
    }
}
