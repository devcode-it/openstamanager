<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Xinax\LaravelGettext\Facades\LaravelGettext;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $manager = LaravelGettext::getFacadeRoot();

        $locale = env('APP_LOCALE', 'it_IT');
        $manager->setLocale($locale);

        return $next($request);
    }
}
