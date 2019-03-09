<?php

namespace Controllers;

use Database;
use Update;

class ConfigController extends Controller
{
    protected static $updateRate = 20;
    protected static $scriptValue = 100;

    public function update($request, $response, $args)
    {
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
        // Aggiornamento in progresso
        if (Update::isUpdateAvailable()) {
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

            $response = $this->twig->render($response, 'config\messages\piece.twig', $args);
        }

        // Aggiornamento completato
        elseif (Update::isUpdateCompleted()) {
            Update::updateCleanup();

            $response = $this->twig->render($response, 'config\messages\done.twig', $args);
        }

        return $response;
    }

    public function configuration($request, $response, $args)
    {
        $args['license'] = file_get_contents(DOCROOT.'/LICENSE');
        $response = $this->twig->render($response, 'config\configuration.twig', $args);

        return $response;
    }

    public function configurationSave($request, $response, $args)
    {
        // Controllo sull'esistenza di nuovi parametri di configurazione
        $host = post('host');
        $database_name = post('database_name');
        $username = post('username');
        $password = post('password');

        // Impostazioni di configurazione strettamente necessarie al funzionamento del progetto
        $backup_config = '<?php

$backup_dir = __DIR__.\'/backup/\';

$db_host = \'|host|\';
$db_username = \'|username|\';
$db_password = \'|password|\';
$db_name = \'|database|\';

';

        $new_config = (file_exists(DOCROOT.'/config.example.php')) ? file_get_contents(DOCROOT.'/config.example.php') : $backup_config;

        $values = [
            '|host|' => $host,
            '|username|' => $username,
            '|password|' => $password,
            '|database|' => $database_name,
        ];
        $new_config = str_replace(array_keys($values), $values, $new_config);

        // Controlla che la scrittura del file di configurazione sia andata a buon fine
        $creation = file_put_contents('config.inc.php', $new_config);

        if (!$creation) {
            $response = $this->twig->render($response, 'config\messages\error.twig', $args);
        } else {
            $response = $response->withRedirect($this->router->pathFor('login'));
        }

        return $response;
    }

    public function configurationTest($request, $response, $args)
    {
        // Controllo sull'esistenza di nuovi parametri di configurazione
        $host = post('host');
        $database_name = post('database_name');
        $username = post('username');
        $password = post('password');

        // Generazione di una nuova connessione al database
        try {
            $database = new \Database($host, $username, $password, $database_name);
        } catch (Exception $e) {
        }

        // Test della configurazione
        if (!empty($database) && $database->isConnected()) {
            $requirements = [
                'SELECT',
                'INSERT',
                'UPDATE',
                'CREATE',
                'ALTER',
                'DROP',
            ];

            $host = str_replace('_', '\_', $database_name);
            $database_name = str_replace('_', '\_', $database_name);
            $username = str_replace('_', '\_', $database_name);

            $results = $database->fetchArray('SHOW GRANTS FOR CURRENT_USER');
            foreach ($results as $result) {
                $privileges = current($result);

                if (
                    str_contains($privileges, ' ON `'.$database_name.'`.*') ||
                    str_contains($privileges, ' ON *.*')
                ) {
                    $pieces = explode(', ', explode(' ON ', str_replace('GRANT ', '', $privileges))[0]);

                    // Permessi generici sul database
                    if (in_array('ALL', $pieces) || in_array('ALL PRIVILEGES', $pieces)) {
                        $requirements = [];
                        break;
                    }

                    // Permessi specifici sul database
                    foreach ($requirements as $key => $value) {
                        if (in_array($value, $pieces)) {
                            unset($requirements[$key]);
                        }
                    }
                }
            }

            // Permessi insufficienti
            if (!empty($requirements)) {
                $state = 1;
            }

            // Permessi completi
            else {
                $state = 2;
            }
        }

        // Connessione fallita
        else {
            $state = 0;
        }

        $response = $response->write($state);

        return $response;
    }
}
