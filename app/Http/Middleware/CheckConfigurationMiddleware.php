<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CheckConfigurationMiddleware
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse|JsonResponse
    {
        $checks = [
            empty(DB::connection()->getDatabaseName()) => [
                'redirect' => 'setup.index',
                'patterns' => [
                    'setup.index',
                    'setup.test',
                    'setup.save',
                ],
            ],
            empty(User::exists()) => [
                'redirect' => 'setup.admin',
                'patterns' => 'setup.admin*',
            ],
        ];

        foreach ($checks as $check => $route) {
            if ($check) {
                return $request->routeIs($route['patterns']) ? $next($request) : redirect()->route($route['redirect']);
            }
        }

        return $next($request);
    }
}
