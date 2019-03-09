<?php

namespace Controllers\Config;

use App;
use Controllers\Controller;
use Database;

class ConfigurationController extends Controller
{
    public static function isConfigured()
    {
        $config = App::getContainer()['config'];

        $valid_config = isset($config['db_host']) && isset($config['db_name']) && isset($config['db_username']) && isset($config['db_password']);

        // Gestione del file di configurazione
        if (file_exists(DOCROOT.'/config.inc.php') && $valid_config && database()->isConnected()) {
            return true;
        }

        return false;
    }

    public function configuration($request, $response, $args)
    {
        $this->permission($request, $response);

        $args['license'] = file_get_contents(DOCROOT.'/LICENSE');
        $response = $this->twig->render($response, 'config\configuration.twig', $args);

        return $response;
    }

    public function configurationSave($request, $response, $args)
    {
        $this->permission($request, $response);

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
        $this->permission($request, $response);

        // Controllo sull'esistenza di nuovi parametri di configurazione
        $host = post('host');
        $database_name = post('database_name');
        $username = post('username');
        $password = post('password');

        // Generazione di una nuova connessione al database
        try {
            $database = new Database($host, $username, $password, $database_name);
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

    protected function permission($request, $response)
    {
        if (self::isConfigured()) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
    }
}
