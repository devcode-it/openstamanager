<?php

namespace Controllers;

class ModuleController extends Controller
{
    public function module($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\controller.php', $args);

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

        // Elenco dei plugin
        $plugins = $args['module']->plugins()->where('position', 'tab')->get()->sortBy('order');

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

        $args['plugins'] = $plugins;
        $args['operations'] = $operations;

        $result = $this->oldModule($args);
        $args = array_merge($args, $result);

        $response = $this->twig->render($response, 'edit.twig', $args);

        return $response;
    }

    public function oldModule($args)
    {
        extract($args);

        $dbo = $database = $this->database;

        // Lettura risultato query del modulo
        $init = $structure->filepath('init.php');
        if (!empty($init)) {
            include_once $init;
        }

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

        // Plugins
        $plugins_content = [];

        $module_record = $record;
        foreach ($plugins as $plugin) {
            $record = $module_record;
            $id_plugin = $plugin['id'];

            // Inclusione di eventuale plugin personalizzato
            if (!empty($plugin['script'])) {
                ob_start();
                include $plugin->getEditFile();
                $result = ob_get_clean();
            } else {
                $bulk = $structure->filepath('bulk.php');
                $bulk = empty($bulk) ? [] : include $bulk;
                $bulk = empty($bulk) ? [] : $bulk;

                $result = [
                    'bulk' => $bulk,
                ];
            }

            $plugins_content[$id_plugin] = $result;
        }

        return [
            'buttons' => $buttons,
            'editor_content' => $content,
            'plugins_content' => $plugins_content,
        ];
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
}
