<?php

namespace Hamoi1\EloquentEncryptAble\Traits;

use Hamoi1\EloquentEncryptAble\Services\EloquentEncryptAbleService;

trait EncryptAble
{
    protected static function bootEncryptAble()
    {
        static::saving(function ($model) {
            $encryptAble = property_exists($model, 'encryptAble') ? $model->encryptAble : [];
            $encryptedData = app(EloquentEncryptAbleService::class)->encryptModelData($model->only($encryptAble), $encryptAble);
            $model->fill($encryptedData);
        });

        static::retrieved(function ($model): void {
            $encryptAble = property_exists($model, 'encryptAble') ? $model->encryptAble : [];

            $decryptedData = app(EloquentEncryptAbleService::class)->decryptModelData($model->only($encryptAble), $encryptAble);
            $model->fill($decryptedData);
        });
    }
}
