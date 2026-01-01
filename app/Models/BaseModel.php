<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\SoftDeletes;

trait BaseModel
{
    use SoftDeletes, HasUuid, HasAuditFields;

    public function initializeBaseModel(): void
    {
        $this->initializeHasUuid();
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public function getIncrementing(): bool
    {
        return false;
    }
}
