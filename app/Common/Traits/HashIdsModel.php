<?php

namespace App\Common\Traits;

use Hashids\Hashids;
use Illuminate\Database\Eloquent\Model;

trait HashIdsModel
{
    /**
     * The minimum hash length.
     */
    private int $minHashLength = 5;

    /**
     * The alphabet string.
     */
    private string $alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * The salt string.
     */
    private function salt(): string
    {
        return $this->getTable().config('app.hash_id_key');
    }

    /**
     * Generates the hash for the current model, based on its id and table name.
     * Not based on the model table name and at least 5 characters long.
     */
    public function generateHash(): ?string
    {
        if (! $this->id) {
            return null;
        }

        $hashids = new Hashids($this->salt(), $this->minHashLength, $this->alphabet);

        return $this->prefix() ? $this->prefix().$hashids->encode($this->id) : null;
    }

    /**
     * Persists the `hash` column right after the model is created, since the
     * hash depends on the auto-incremented id.
     */
    protected static function bootHashIdsModel(): void
    {
        static::created(function (Model $model) {
            $model->forceFill(['hash' => $model->generateHash()])->saveQuietly();
        });
    }

    /**
     * The default is the first two letters of the model table name.
     * Tables with a composite name returns the first letters of the words that compose it.
     * Ex.: product_photos -> prp
     *      sales        -> sal.
     */
    public function prefix(): string
    {
        if (! strpos($this->modelHashPrefix(), '_')) {
            return $this->modelHashPrefix()[0].$this->modelHashPrefix()[1].$this->modelHashPrefix()[2];
        }

        $prefix = explode('_', $this->modelHashPrefix());

        return $prefix[0][0].$prefix[0][1].$prefix[1][0];
    }

    /**
     * Set the prefix table by prefix name or table.
     */
    protected function modelHashPrefix(): string
    {
        return $this->getTable();
    }
}
