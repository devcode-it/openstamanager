<?php

namespace Middlewares;

use Modules;
use Plugins;

/**
 * Classe per l'impostazione automatica delle variabili rilevanti per il funzionamento del progetto.
 *
 * @since 2.5
 */
class ContentMiddleware extends Middleware
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

        $args['formatter'] = formatter()->getNumberSeparators();

        $args['version'] = \Update::getVersion();
        $args['revision'] = \Update::getRevision();

        $args['is_ajax'] = $request->isXhr();

        // Periodo di visualizzazione
        if (!empty($_GET['period_start'])) {
            $_SESSION['period_start'] = $_GET['period_start'];
            $_SESSION['period_end'] = $_GET['period_end'];
        }
        // Dal 01-01-yyy al 31-12-yyyy
        elseif (!isset($_SESSION['period_start'])) {
            $_SESSION['period_start'] = date('Y').'-01-01';
            $_SESSION['period_end'] = date('Y').'-12-31';
        }

        $args['calendar'] = [
            'start' => $_SESSION['period_start'],
            'end' => $_SESSION['period_end'],
            'color' => ($_SESSION['period_start'] != date('Y').'-01-01' || $_SESSION['period_end'] != date('Y').'-12-31') ? 'red' : 'white',
        ];

        // Argomenti di ricerca dalla sessione
        $search = [];
        $array = $_SESSION['module_'.$id_module];
        if (!empty($array)) {
            foreach ($array as $field => $value) {
                if (!empty($value) && starts_with($field, 'search_')) {
                    $field_name = str_replace('search_', '', $field);

                    $search[$field_name] = $value;
                }
            }
        }
        $args['search'] = $search;

        // Menu principale
        $args['main_menu'] = Modules::getMainMenu();

        // Impostazione degli argomenti
        $request = $this->setArgs($request, $args);

        return $next($request, $response);
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
