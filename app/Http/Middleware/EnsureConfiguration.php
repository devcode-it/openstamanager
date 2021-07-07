<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\InitializationController;
use App\Http\Controllers\RequirementsController;
use App\Http\Controllers\UpdateController;
use Closure;
use Illuminate\Http\Request;

class EnsureConfiguration
{
    /**
     * @return string|null
     */
    protected $redirect_route = null;

    /**
     * @param null $redirect
     */
    public function setRedirect($redirect): void
    {
        $this->redirect_route = $redirect;
    }

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

        // Controlli in ordine per l'esecizione
        $checks = [
            'checkRequirements', // Test sui requisiti del gestionale
            'checkConfiguration', // Test della connessione al database
            'checkMigrations', // Verifiche sullo stato delle migrazioni
            'checkInitialization', // Verifiche sullo stato delle impostazioni obbligatorie
        ];

        foreach ($checks as $check) {
            $continue = $this->{$check}($route);

            // Redirect automatico causato dal controllo corrente
            if (isset($this->redirect_route)) {
                return redirect($this->redirect_route);
            }

            // Blocco sui controlli in caso di mancato completamento del controllo corrente
            if (!$continue) {
                break;
            }
        }

        return $next($request);
    }

    /**
     * Controlli sui requisiti minimi dell'ambiente di esecuzione.
     *
     * @param $route
     *
     * @return bool
     */
    protected function checkRequirements($route)
    {
        $configuration_paths = ['requirements'];
        $requirements_satisfied = RequirementsController::isSatisfied();

        if ($requirements_satisfied) {
            // Redirect nel caso in cui i requisiti siano soddisfatti
            if (in_array($route->getName(), $configuration_paths)) {
                $this->setRedirect(route('configuration'));
            }
        } else {
            // Redirect per requisiti incompleti
            if (!in_array($route->getName(), $configuration_paths)) {
                $this->setRedirect(route('requirements'));
            }
        }

        return $requirements_satisfied;
    }

    /**
     * Controlli sulla configurazione del gestionale.
     *
     * @param $route
     *
     * @return bool
     */
    protected function checkConfiguration($route)
    {
        $configuration_paths = ['configuration', 'configuration-save', 'configuration-write', 'configuration-test'];
        $configuration_completed = ConfigurationController::isConfigured();

        if ($configuration_completed) {
            // Redirect nel caso in cui la configurazione sia correttamente funzionante
            if (in_array($route->getName(), $configuration_paths)) {
                $this->setRedirect(route('update'));
            }
        } else {
            // Redirect per configurazione mancante
            if (!in_array($route->getName(), $configuration_paths)) {
                $this->setRedirect(route('configuration'));
            }
        }

        return $configuration_completed;
    }

    /**
     * Controlli sulle migrazioni (aggiornamenti) del gestionale.
     *
     * @param $route
     *
     * @return bool
     */
    protected function checkMigrations($route)
    {
        $update_paths = ['update', 'update-execute'];
        $update_completed = UpdateController::isCompleted();

        if ($update_completed) {
            // Redirect nel caso in cui la configurazione sia correttamente funzionante
            if (in_array($route->getName(), $update_paths)) {
                $this->setRedirect(route('initialization'));
            }
        } else {
            // Redirect per configurazione mancante
            if (!in_array($route->getName(), $update_paths)) {
                $this->setRedirect(route('update'));
            }
        }

        return $update_completed;
    }

    /**
     * Controlli sull'inizializzazione delle informazioni di base del gestionale.
     *
     * @param $route
     *
     * @return bool
     */
    protected function checkInitialization($route)
    {
        $initialization_paths = ['initialization', 'initialization-save'];
        $initialization_completed = InitializationController::isInitialized();

        if ($initialization_completed) {
            // Redirect nel caso in cui la configurazione sia correttamente funzionante
            if (in_array($route->getName(), $initialization_paths)) {
                $this->setRedirect(route('login'));
            }
        } else {
            // Redirect per configurazione mancante
            if (!in_array($route->getName(), $initialization_paths)) {
                $this->setRedirect(route('initialization'));
            }
        }

        return $initialization_completed;
    }
}
