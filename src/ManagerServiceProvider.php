<?php

namespace VagKaefer\LaravelSimplifiedTranslationManager;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use VagKaefer\LaravelSimplifiedTranslationManager\Console\ProcessCommand;

class ManagerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/simplified-translation-manager.php', 'simplified-translation-manager');
    }

    public function boot()
    {
        // Register the command if we are using the application by the CLI
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/config/simplified-translation-manager.php' => config_path('simplified-translation-manager.php'),
            ], 'config');

            $this->commands([
                ProcessCommand::class,
            ]);

            Config::set('filesystems.disks.translations', [
                'driver' => 'local',
                'root' => base_path() . '/resources/lang/'
            ]);
        }
    }
}
