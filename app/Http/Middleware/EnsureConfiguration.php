<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\InitializationController;
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
        $result = $this->checkConfiguration($route);
        if ($result !== null) {
            return $result;
        }

        // Verifiche sullo stato delle migrazioni
        $result = $this->checkMigrations($route);
        if ($result !== null) {
            return $result;
        }

        // Verifiche sullo stato delle impostazioni obbligatorie
        $result = $this->checkInitialization($route);
        if ($result !== null) {
            return $result;
        }

        return $next($request);
    }

    protected function checkConfiguration($route)
    {
        // Test della connessione al database
        $configuration_paths = ['configuration', 'configuration-save', 'configuration-test'];
        $configuration_completed = ConfigurationController::isConfigured();

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

        return null;
    }

    protected function checkInitialization($route)
    {
        $initialization_paths = ['initialization', 'initialization-save'];
        $initialization_completed = InitializationController::isInitialized();

        if ($initialization_completed) {
            // Redirect nel caso in cui la configurazione sia correttamente funzionante
            if (in_array($route->getName(), $initialization_paths)) {
                return redirect(route('login'));
            }
        } else {
            // Redirect per configurazione mancante
            if (!in_array($route->getName(), $initialization_paths)) {
                return redirect(route('initialization'));
            }
        }

        return null;
    }

    protected function checkMigrations($route)
    {
        return null;
    }
}
