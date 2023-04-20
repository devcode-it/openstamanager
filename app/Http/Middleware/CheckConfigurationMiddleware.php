<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PDOException;

class CheckConfigurationMiddleware
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse|JsonResponse
    {
        $checks = [
            'database' => fn () => !empty(DB::connection()->getDatabaseName()) && DB::connection()->getPdo(),
            'admin_user' => fn () => !empty(User::exists()),
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
                if (str_starts_with($request->route()?->getName(), 'setup.')) {
                    return $next($request);
                }

                return $request->wantsJson()
                    ? \response()->json(['message' => __('Configurazione del database richiesta')], Response::HTTP_SERVICE_UNAVAILABLE)
                    : redirect()->route('setup.index');
            }
        }

        return $next($request);
    }
}
