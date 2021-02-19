<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ConfigurationController;
use Closure;
use Illuminate\Http\Request;

class EnsureConfiguration
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();
        if (starts_with($route->parameter('path'), 'assets')) {
            return $next($request);
        }

        // Test della connessione al database
        $configuration_paths = ['configuration', 'configuration-save', 'configuration-test'];
        $configuration_completed = ConfigurationController::isConfigCompleted();

        if ($configuration_completed) {
            // Redirect nel caso in cui la configurazione sia correttamente funzionante
            if (in_array($route->getName(), $configuration_paths)) {
                return redirect(route('initialization'));
            }
        } else {
            // Redirect per configurazione mancante
            if (!in_array($route->getName(), $configuration_paths)) {
                return redirect(route('configuration'));
            }
        }

        // Verifiche sullo stato delle migrazioni

        // Verifiche sullo stato delle impostazioni obbligatorie

        return $next($request);
    }
}
