<?php

namespace DevCode\Aggiornamenti;

use Illuminate\Support\ServiceProvider as BaseProvider;

class ServiceProvider extends BaseProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'devcode-it');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'aggiornamenti');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/aggiornamenti.php', 'aggiornamenti');

        // Register the service the package provides.
        $this->app->singleton('aggiornamenti', function ($app) {
            return new Modulo();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['aggiornamenti'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/aggiornamenti.php' => config_path('aggiornamenti.php'),
        ], 'aggiornamenti.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/devcode-it'),
        ], 'aggiornamenti.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/devcode-it'),
        ], 'aggiornamenti.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/devcode-it'),
        ], 'aggiornamenti.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
