<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait BaseModel
{
    use SoftDeletes;

    /**
     * Use UUIDs as primary keys and disable auto-increment.
     */
    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Boot the BaseModel trait for a model.
     *
     * This will:
     * - set a UUID primary key on create
     * - fill created_by/updated_by on create
     * - fill updated_by on update
     * - fill deleted_by on soft delete
     */
    protected static function bootBaseModel(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            if (Auth::check()) {
                $userId = Auth::id();

                if (empty($model->created_by)) {
                    $model->created_by = $userId;
                }

                $model->updated_by = $userId;
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
                // Avoid firing events again when persisting deleted_by
                $model->saveQuietly();
            }
        });
    }
}
