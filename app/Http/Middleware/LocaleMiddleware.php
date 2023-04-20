<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LocaleMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        app()->setLocale(session()->get('locale', app()->getLocale()));

        return $next($request);
    }
}
