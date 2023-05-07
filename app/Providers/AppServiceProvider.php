<?php

namespace App\Providers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @throws Exception
     *
     * @noinspection StaticClosureCanBeUsedInspection — Throws Cannot bind an instance to a static closure
     * @noinspection AnonymousFunctionStaticInspection — Throws Cannot bind an instance to a static closure
     */
    public function boot(Controller $controller): void
    {
        view()->share('modules', $controller->getModules());
        Vite::macro('image', fn (string $asset) => Vite::asset("resources/images/$asset"));
    }
}
