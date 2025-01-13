<?php

declare(strict_types=1);

namespace Hamoi1\EloquentEncryptAble;

use Hamoi1\EloquentEncryptAble\Console\Commands\ReEncryptDataCommand;
use Hamoi1\EloquentEncryptAble\Services\EloquentEncryptAbleService;
use Illuminate\Support\ServiceProvider;

class EloquentEncryptAbleServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register the command
        $this->commands([
            ReEncryptDataCommand::class,
        ]);

        // Merge the config file
        $this->mergeConfigFrom(__DIR__ . '/../config/eloquent-encryptable.php', 'eloquent-encryptable.php');

        // Register the service
        $this->app->singleton(EloquentEncryptAbleService::class, function ($app) {
            return new EloquentEncryptAbleService;
        });
    }

    public function boot()
    {
        // Publish the config file (eloquent-encryptable.php)
        $this->publishes([
            __DIR__ . '/../config/eloquent-encryptable.php' => config_path('eloquent-encryptable.php'),
        ], 'config');
    }
}
