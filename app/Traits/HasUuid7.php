<?php

namespace App\Traits;

use Illuminate\Events\QueuedClosure;
use Illuminate\Support\Str;

trait HasUuid7
{
    public static function bootHasUuid7(): void
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid7();
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @see \Illuminate\Database\Eloquent\Model::getIncrementing
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @see \Illuminate\Database\Eloquent\Model::getKeyType
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Register a creating model event with the dispatcher.
     *
     * @see \Illuminate\Database\Eloquent\Concerns\HasEvents::creating
     *
     * @param  QueuedClosure|callable|array|class-string  $callback
     * @return void
     */
    abstract public static function creating($callback);
}
