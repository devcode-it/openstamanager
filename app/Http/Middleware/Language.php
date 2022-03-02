<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\LaravelGettext\Facades\LaravelGettext;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $manager = LaravelGettext::getFacadeRoot();

        $locale = env('APP_LOCALE', 'it_IT');
        $manager->setLocale($locale);
        app()->setLocale($locale);

        return $next($request);
    }
}
