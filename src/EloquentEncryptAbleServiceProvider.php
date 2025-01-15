<?php

declare(strict_types=1);

namespace Hamoi1\EloquentEncryptAble;

use Hamoi1\EloquentEncryptAble\Console\Commands\ReEncryptDataCommand;
use Hamoi1\EloquentEncryptAble\Services\EloquentEncryptAbleService;
use Illuminate\Support\Facades\Blade;
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
        $this->mergeConfigFrom(__DIR__ . '/../config/eloquent-encryptable.php', 'eloquent-encryptable');

        // Register the service
        $this->app->singleton('eloquent-encryptable', function () {
            return app(EloquentEncryptAbleService::class);
        });

        // Load the Blade directives
        $this->loadBladeDirectives();
    }

    public function boot()
    {
        // Publish the config file (eloquent-encryptable.php)
        $this->publishes([
            __DIR__ . '/../config/eloquent-encryptable.php' => config_path('eloquent-encryptable.php'),
        ], 'config');
    }


    protected function loadBladeDirectives()
    {
        Blade::directive('decrypt', function ($expression) {
            $params = explode(',', $expression);
            // $expression[0]: The value to decrypt
            // $expression[1]: The default value (optional)
            $value = trim($params[0]);
            $default = isset($params[1]) ? trim($params[1]) : '"N/A"';

            return "<?php echo {$value} ? app('eloquent-encryptable')->decrypt({$value}) : {$default}; ?>";
        });

        Blade::directive('encrypt', function ($expression) {
            return "<?php echo app('eloquent-encryptable')->encrypt({$expression}); ?>";
        });
    }
}
