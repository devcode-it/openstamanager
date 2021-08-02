<?php

namespace DevCode\CausaliTrasporto;

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
        //$this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'causali-trasporto');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'causali-trasporto');
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
        $this->mergeConfigFrom(__DIR__.'/../config/causali-trasporto.php', 'causali-trasporto');

        // Register the service the package provides.
        $this->app->singleton('causali-trasporto', function ($app) {
            return new CausaliTrasporto;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['causali-trasporto'];
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
            __DIR__.'/../config/causali-trasporto.php' => config_path('causali-trasporto.php'),
        ], 'causali-trasporto');

        // Publishing assets.
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/devcode-it'),
        ], 'causali-trasporto');

        // Publishing the translation files.
        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/devcode-it'),
        ], 'causali-trasporto');

        // Registering package commands.
        // $this->commands([]);
    }
}
