<?php

namespace Controllers;

class ModuleController extends Controller
{
    public function module($request, $response, $args)
    {
        // Elenco dei plugin
        $plugins = $args['module']->plugins()->where('position', 'tab_main')->get()->sortBy('order');
        $args['plugins'] = $plugins;

        $result = $this->oldController($args);
        $args = array_merge($args, $result);
        $args['custom_content'] = $args['content'];

        $response = $this->twig->render($response, 'old/controller.twig', $args);

        return $response;
    }

    public function edit($request, $response, $args)
    {
        // Rimozione record precedenti sulla visita della pagina
        $this->database->delete('zz_semaphores', [
            'id_utente' => $args['user']['id'],
            'posizione' => $args['module_id'].', '.$args['record_id'],
        ]);

        // Creazione nuova visita
        $this->database->insert('zz_semaphores', [
            'id_utente' => $args['user']['id'],
            'posizione' => $args['module_id'].', '.$args['record_id'],
        ]);

        // Elenco delle operazioni
        $operations = $this->database->fetchArray('SELECT `zz_operations`.*, `zz_users`.`username` FROM `zz_operations`
            JOIN `zz_users` ON `zz_operations`.`id_utente` = `zz_users`.`id`
            WHERE id_module = '.prepare($args['module_id']).' AND id_record = '.prepare($args['record_id']).'
        ORDER BY `created_at` ASC LIMIT 200');

        foreach ($operations as $operation) {
            $description = $operation['op'];
            $icon = 'pencil-square-o';
            $color = null;
            $tags = null;

            switch ($operation['op']) {
                case 'add':
                $description = tr('Creazione');
                $icon = 'plus';
                $color = 'success';
                break;

                case 'update':
                $description = tr('Modifica');
                $icon = 'pencil';
                $color = 'info';
                break;

                case 'delete':
                $description = tr('Eliminazione');
                $icon = 'times';
                $color = 'danger';
                break;

                default:
                $tags = ' class="timeline-inverted"';
                break;
            }

            $operation['tags'] = $tags;
            $operation['color'] = $color;
            $operation['icon'] = $icon;
            $operation['description'] = $description;
        }

        $args['operations'] = $operations;
        $args['include_operations'] = true;

        // Elenco dei plugin
        $plugins = $args['module']->plugins()->where('position', 'tab')->get()->sortBy('order');
        $args['plugins'] = $plugins;

        $result = $this->oldEditor($args);
        $args = array_merge($args, $result);

        $response = $this->twig->render($response, 'old/editor.twig', $args);

        return $response;
    }

    public function editRecord($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\actions.php', $args);

        return $response;
    }

    public function add($request, $response, $args)
    {
        $args['query_params'] = $request->getQueryParams();
        $response = $this->view->render($response, 'resources\views\add.php', $args);

        return $response;
    }

    public function addRecord($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\actions.php', $args);

        $response = $response->withRedirect($this->router->pathFor('module-record'));

        return $response;
    }

    protected function oldEditor($args)
    {
        extract($args);

        $dbo = $database = $this->database;

        // Lettura risultato query del modulo
        $init = $structure->filepath('init.php');
        if (!empty($init)) {
            include $init;
        }

        $args['record'] = $record;

        $content = $structure->filepath('edit.php');
        if (!empty($content)) {
            ob_start();
            include $content;
            $content = ob_get_clean();
        }

        $buttons = $structure->filepath('buttons.php');
        if (!empty($buttons)) {
            ob_start();
            include $buttons;
            $buttons = ob_get_clean();
        }

        $module_bulk = $structure->filepath('bulk.php');
        $module_bulk = empty($module_bulk) ? [] : include $module_bulk;
        $module_bulk = empty($module_bulk) ? [] : $module_bulk;

        return [
            'buttons' => $buttons,
            'editor_content' => $content,
            'bulk' => $module_bulk,
            'plugins_content' => $this->oldPlugins($args),
        ];
    }

    protected function oldPlugins($args)
    {
        extract($args);

        $dbo = $database = $this->database;

        // Plugins
        $plugins_content = [];

        $module_record = $record;
        foreach ($args['plugins'] as $plugin) {
            $record = $module_record;
            $id_plugin = $plugin['id'];

            $bulk = null;
            $content = null;

            // Inclusione di eventuale plugin personalizzato
            if (!empty($plugin['script']) || $plugin->option == 'custom') {
                ob_start();
                include $plugin->getEditFile();
                $content = ob_get_clean();
            } else {
                $bulk = $args['structure']->filepath('bulk.php');
                $bulk = empty($bulk) ? [] : include $bulk;
                $bulk = empty($bulk) ? [] : $bulk;
            }

            $plugins_content[$id_plugin] = [
                'content' => $content,
                'bulk' => $bulk,
            ];
        }

        return $plugins_content;
    }

    protected function oldController($args)
    {
        extract($args);

        $dbo = $database = $this->database;

        if ($args['structure']->option == 'custom') {
            // Lettura risultato query del modulo
            $init = $args['structure']->filepath('init.php');
            if (!empty($init)) {
                include $init;
            }

            $args['record'] = $record;

            $content = $args['structure']->filepath('edit.php');
            if (!empty($content)) {
                ob_start();
                include $content;
                $content = ob_get_clean();
            }
        }

        return [
            'content' => $content,
            'plugins_content' => $this->oldPlugins($args),
        ];
    }
}
