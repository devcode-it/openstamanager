<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PDO;
use PDOException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CheckConfigurationMiddleware
{
    public function handle(Request $request, Closure $next): Response|SymfonyResponse
    {
        $checks = [
            'database' => static fn (): bool => !empty(DB::connection()->getDatabaseName()) && DB::connection()->getPdo() instanceof PDO,
            'admin_user' => static fn (): bool => !empty(User::exists()),
        ];

        foreach ($checks as $check) {
            try {
                $check = $check();
            } catch (QueryException|InvalidArgumentException|PDOException $exception) {
                $check = false;
                logger()->error(
                    __('Configurazione del database mancante: ').$exception->getMessage(),
                    $exception->getTrace()
                );
            }

            if (!$check) {
                $route = $request->route();
                if ($route && str_starts_with($route->getName(), 'setup.')) {
                    return $next($request);
                }

                if ($request->wantsJson()) {
                    return \response()->json(['message' => __('Configurazione del database richiesta')], Response::HTTP_SERVICE_UNAVAILABLE);
                }

                return redirect()->route('setup.index');
            }
        }

        return $next($request);
    }
}
