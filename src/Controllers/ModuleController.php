<?php

namespace Controllers;

use Modules;
use Plugins;

class ModuleController extends Controller
{
    public function index($request, $response, $args)
    {
        $args = self::argsPrepare($args);
        $response = $this->view->render($response, 'resources\views\controller.php', $args);

        return $response;
    }

    public function record($request, $response, $args)
    {
        $args = self::argsPrepare($args);
        $response = $this->view->render($response, 'resources\views\editor.php', $args);

        return $response;
    }

    public function add($request, $response, $args)
    {
        $args = self::argsPrepare($args);
        $response = $this->view->render($response, 'resources\views\add.php', $args);

        return $response;
    }

    public static function argsPrepare($args)
    {
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

        return $args;
    }
}
