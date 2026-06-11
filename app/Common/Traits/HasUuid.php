<?php

namespace App\Common\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Trait HasUuid
     *
     * This trait adds a UUID field to the model and automatically generates a UUID when a new model is created.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid()->toString();
        });
    }
}
