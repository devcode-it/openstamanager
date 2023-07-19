<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PDO;
use PDOException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CheckConfigurationMiddleware
{
    public function handle(Request $request, Closure $next): Response|SymfonyResponse
    {
        // Skip log viewer routes
        if (str_starts_with($request->route()->getName(), 'log-viewer.')) {
            return $next($request);
        }
        $checks = [
            'database' => static fn (): bool => ! empty(DB::connection()->getDatabaseName()) && DB::connection()->getPdo() instanceof PDO,
            'admin_user' => static fn (): bool => ! empty(User::exists()),
        ];

        foreach ($checks as $name => $check) {
            try {
                $check = $check();
            } catch (QueryException|InvalidArgumentException|PDOException $exception) {
                $check = false;
                logger()->error(
                    __('Configurazione del database mancante: ').$exception->getMessage(),
                    $exception->getTrace()
                );
            }

            if (! $check) {
                $route = $request->route();
                if ($route && str_starts_with($route->getName(), 'setup.')) {
                    return $next($request);
                }

                if ($request->wantsJson()) {
                    return \response()->json(['message' => __('Configurazione del database richiesta')], Response::HTTP_SERVICE_UNAVAILABLE);
                }

                return redirect()->route('setup.index', ['step' => $name !== 'database' ? $name : null]);
            }
        }

        // Redirect to login if we are in setup route
        $route = $request->route();
        if ($route instanceof Route && str_starts_with($route->getName(), 'setup.')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
