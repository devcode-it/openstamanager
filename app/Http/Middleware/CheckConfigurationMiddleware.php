<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CheckConfigurationMiddleware
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $checks = [
            empty(DB::connection()->getDatabaseName()) => 'setup.index',
            empty(User::exists()) => 'setup.admin',
        ];

        foreach ($checks as $check => $route) {
            if ($check) {
                return $request->routeIs($route) ? $next($request) : redirect()->route($route);
            }
        }

        return $next($request);
    }
}
