<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class LocaleMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            app()->setLocale($request
                ->user()
                ?->settings()
                ->get('locale', app()->getLocale())
                ?? session('locale', app()->getLocale())
            );
        } catch (QueryException) {
            // Do nothing, since DB is not configured yet
        }

        return $next($request);
    }
}
