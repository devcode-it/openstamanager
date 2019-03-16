<?php

namespace Middlewares;

use Modules;
use Plugins;
use Util\Query;

/**
 * Classe per l'impostazione automatica delle variabili rilevanti per il funzionamento del progetto.
 *
 * @since 2.5
 */
class ModuleMiddleware extends Middleware
{
    public function __invoke($request, $response, $next)
    {
        $route = $request->getAttribute('route');
        if (!$route) {
            return $next($request, $response);
        }

        $args = $route->getArguments();
        Modules::setCurrent($args['module_id']);
        Plugins::setCurrent($args['plugin_id']);

        // Variabili fondamentali
        $module = Modules::getCurrent();
        $plugin = Plugins::getCurrent();
        $structure = isset($plugin) ? $plugin : $module;

        $id_module = $module['id'];
        $id_plugin = $plugin['id'];

        $args['id_module'] = $id_module;
        $args['id_plugin'] = $id_plugin;
        $args['id_record'] = $args['record_id'];

        $args['structure'] = $structure;
        $args['plugin'] = $plugin;
        $args['module'] = $module;

        $user = auth()->getUser();
        $args['user'] = $user;

        $request = $this->setArgs($request, $args);

        // Controllo sui permessi di accesso alla struttura
        $enabled = ['r', 'rw'];
        $permission = in_array($structure->permission, $enabled);

        // Controllo sui permessi di accesso al record
        if (!empty($args['id_record'])) {
            $permission &= $this->recordAccess($args);
        }

        if (!$permission) {
            $response = $this->twig->render($response, 'errors\403.twig', $args);

            return $response->withStatus(403);
        } else {
            $response = $next($request, $response);
        }

        return $response;
    }

    protected function recordAccess($args)
    {
        Query::setSegments(false);
        $query = Query::getQuery($args['structure'], [
            'id' => $args['id_record'],
        ]);
        Query::setSegments(true);

        $has_access = !empty($query) ? $this->database->fetchNum($query) !== 0 : true;

        return $has_access;
    }

    protected function setArgs($request, $args)
    {
        $route = $request->getAttribute('route');

        // update the request with the new arguments to route
        $route->setArguments($args);
        $request = $request->withAttribute('route', $route);

        // also update the routeInfo attribute so that we are consistent
        $routeInfo = $request->getAttribute('routeInfo');
        $routeInfo[2] = $args;
        $request = $request->withAttribute('route', $route);

        return $request;
    }
}
