<?php

namespace Controllers\Config;

use Controllers\Controller;
use Update;

class UpdateController extends Controller
{
    protected static $updateRate = 20;
    protected static $scriptValue = 100;

    public function update($request, $response, $args)
    {
        $this->permission($request, $response);

        $total = 0;
        $updates = Update::getTodoUpdates();

        if (!Update::isUpdateAvailable()) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        foreach ($updates as $update) {
            if ($update['sql'] && (!empty($update['done']) || is_null($update['done']))) {
                $queries = readSQLFile(DOCROOT.$update['directory'].$update['filename'].'.sql', ';');
                $total += count($queries);

                if (intval($update['done']) > 1) {
                    $total -= intval($update['done']) - 2;
                }
            }

            if ($update['script']) {
                $total += self::$scriptValue;
            }
        }

        // Inizializzazione
        if (Update::isUpdateLocked() && filter('force') === null) {
            $response = $this->twig->render($response, 'config\messages\blocked.twig', $args);
        } else {
            $args = array_merge($args, [
                'installing' => intval(!$this->database->isInstalled()),
                'total_updates' => count($updates),
                'total_count' => $total,
            ]);

            $response = $this->twig->render($response, 'config\update.twig', $args);
        }

        return $response;
    }

    public function updateProgress($request, $response, $args)
    {
        $this->permission($request, $response);

        // Aggiornamento in progresso
        $update = Update::getCurrentUpdate();

        $result = Update::doUpdate(self::$updateRate);

        $args = array_merge($args, [
            'update_name' => $update['name'],
            'update_version' => $update['version'],
            'update_filename' => $update['filename'],
        ]);

        if (!empty($result)) {
            $rate = 0;
            if (is_array($result)) {
                $rate = $result[1] - $result[0];
            } elseif (!empty($update['script'])) {
                $rate = self::$scriptValue;
            }

            $args = array_merge($args, [
                'show_sql' => is_array($result) && $result[1] == $result[2],
                'show_script' => is_bool($result),
                'rate' => $rate,
            ]);
        }

        $args['is_completed'] = false;
        if (is_bool($result)) {
            Update::updateCleanup();

            $args['is_completed'] = count(Update::getTodoUpdates()) == 1;
        }

        $response = $this->twig->render($response, 'config\messages\piece.twig', $args);

        return $response;
    }

    protected function permission($request, $response)
    {
        if (!ConfigurationController::isConfigured() || !Update::isUpdateAvailable()) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
    }
}
