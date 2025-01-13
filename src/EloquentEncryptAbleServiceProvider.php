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
        $this->mergeConfigFrom(__DIR__ . '/../config/hill-cipher.php', 'hill-cipher');

        // Register the service
        $this->app->singleton(EloquentEncryptAbleService::class, function ($app) {
            return new EloquentEncryptAbleService;
        });
    }

    public function boot()
    {
        // Publish the config file (hill-cipher.php)
        $this->publishes([
            __DIR__ . '/../config/hill-cipher.php' => config_path('hill-cipher.php'),
        ], 'config');
    }
}
