<?php

namespace App\Providers;

use App\Http\Controllers\Controller;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\RestifyApplicationServiceProvider;
use Illuminate\Support\Facades\Gate;

class RestifyServiceProvider extends RestifyApplicationServiceProvider
{
    /**
     * Register the Restify gate.
     *
     * This gate determines who can access Restify in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewRestify', function ($user) {
            return true;
        });
    }

    protected function repositories(): void
    {
        parent::repositories();

        // Register repositories from modules
        $modules = app(Controller::class)->getModules();
        foreach ($modules as $module) {
            Restify::repositoriesFrom($module['modulePath'].DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Api', $module['namespace'].'\Api\\');
        }
    }
}
