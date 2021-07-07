<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Controller dedicato alla gestione della configurazione di base del gestionale per la piattaforma in utilizzo.
 */
class ConfigurationController extends Controller
{
    /**
     * Verifica se la configurazione del gestionale per la piattaforma corrente è stata completata correttamente.
     *
     * @return bool
     */
    public static function isConfigured()
    {
        try {
            $connection = DB::connection();
            $configuration_completed = !empty($connection->getDatabaseName());
            $grants = $connection->select($connection->raw('SHOW GRANTS FOR CURRENT_USER'));
        } catch (\Exception $e) {
            $configuration_completed = false;
        }

        return $configuration_completed;
    }

    public function index(Request $request)
    {
        // Impostazione dinamica della lingua
        $lang = $request->get('lang');
        if (!empty($lang)) {
            app()->setLocale($lang);
        }

        // Contenuti aggiuntivi
        $args = [
            'license' => file_get_contents(base_path('LICENSE')),
            'languages' => [
                'it_IT' => [
                    'title' => tr('Italiano'),
                    'flag' => 'IT',
                ],
                'en_GB' => [
                    'title' => tr('Inglese'),
                    'flag' => 'GB',
                ],
            ],

            // Default values
            'host' => '',
            'username' => '',
            'database_name' => '',
        ];

        return view('config.configuration', $args);
    }

    /**
     * Metodo per la gestione della validazione della configurazione indicata.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function test(Request $request)
    {
        $requirements = $this->checkConnection($request);

        if ($requirements === null) {
            $state = 0;
        }

        // Permessi insufficienti
        elseif (!empty($requirements)) {
            $state = 1;
        }

        // Permessi completi
        else {
            $state = 2;
        }

        return response()->json(['test' => $state]);
    }

    /**
     * Metodo per l'esecuzione della pulizia automatica per la cache della configurazione.
     */
    public function cache(Request $request)
    {
        // Refresh della cache sulla configurazione
        Artisan::call('config:cache');

        return redirect(route('configuration'));
    }

    /**
     * Metodo per la gestione del messaggio di errore alla scrittura fisica della configurazione.
     */
    public function write(Request $request)
    {
        $params = $request->old();
        $env = $this->buildEnvFrom($params);

        return view('config.configuration-writing', [
            'config' => $env,
            'params' => $params,
        ]);
    }

    /**
     * Metodo indirizzato al salvataggio della configurazione.
     */
    public function save(Request $request)
    {
        // Controllo sullo stato della connessione
        $result = $this->checkConnection($request);
        if ($result === null) {
            return redirect(route('configuration'));
        }

        $env = $this->buildEnvFrom($request->all());

        // Scrittura fisica della configurazione
        $path = base_path('.env');
        $result = file_put_contents($path, $env);

        // Redirect in caso di fallimento
        if ($result === false) {
            return redirect(route('configuration-write'))
                ->withInput();
        }

        return redirect(route('configuration-cache'));
    }

    /**
     * Verifica la connessione al database secondo i parametri indicati nella richiesta.
     * Restituisce un array di permessi mancanti in caso la connessione avvenga con successo, oppure null in caso contrario.
     *
     * @return array|string[]|null
     */
    protected function checkConnection(Request $request)
    {
        // Configurazione della connessione di test
        $database_name = $request->input('database_name');
        config(['database.connections.testing' => [
            'driver' => 'mysql',
            'host' => $request->input('host'),
            'port' => '3306',
            'password' => $request->input('password'),
            'database' => $database_name,
            'username' => $request->input('username'),
        ]]);

        try {
            $connection = DB::connection('testing');

            // Controllo sul nome del database per verificare la connessione
            $connection_completed = !empty($connection->getDatabaseName());

            // Individuazione permessi garantiti all'utenza
            $database_name = str_replace('_', '\_', $database_name);
            $grants = $connection->select($connection->raw('SHOW GRANTS FOR CURRENT_USER'));
        } catch (\Exception $e) {
            return null;
        }

        $requirements = [
            'SELECT',
            'INSERT',
            'UPDATE',
            'CREATE',
            'ALTER',
            'DROP',
        ];

        foreach ($grants as $result) {
            $privileges = current($result);

            if (
                string_contains($privileges, ' ON `'.$database_name.'`.*') ||
                string_contains($privileges, ' ON *.*')
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

        return $requirements;
    }

    /**
     * Definisce i nuovi contenuti per il file .env sulla base dell'input utente.
     *
     * @param array $params
     */
    protected function buildEnvFrom($params): string
    {
        /*
        // Individuazione parametri aggiuntivi
        $decimals = $params['decimal_separator'];
        $thousands = $params['thousand_separator'];
        $decimals = $decimals == 'dot' ? '.' : ',';
        $thousands = $thousands == 'dot' ? '.' : $thousands;
        $thousands = $thousands == 'comma' ? ',' : $thousands;
        */

        // Completamento configurazione
        $pairs = [
            'APP_LOCALE' => $params['language'],

            'DB_HOST' => $params['host'],
            'DB_USERNAME' => $params['username'],
            'DB_PASSWORD' => $params['password'],
            'DB_DATABASE' => $params['database_name'],

            /*
            '|timestamp|' => post('timestamp_format'),
            '|date|' => post('date_format'),
            '|time|' => post('time_format'),
            '|decimals|' => $decimals,
            '|thousands|' => $thousands,
            */
        ];

        $env = $this->buildEnv($pairs);

        return $env;
    }

    /**
     * Definisce i nuovi contenuti per il file .env sulla base della configurazione indicata.
     *
     * @param $config
     */
    protected function buildEnv($config): string
    {
        $file = base_path('.env');
        $content = file_get_contents($file);

        foreach ($config as $key => $value) {
            $content = str_replace(
                "$key=".$this->getCurrentEnvValue($key),
                "$key=".$value,
                $content
            );
        }

        return $content;
    }

    /**
     * Restituisce il valore (fisico) corrente per una chiave del file .env.
     *
     * @param $key
     *
     * @return mixed|string
     */
    protected function getCurrentEnvValue($key)
    {
        if (is_bool(env($key))) {
            $old = env($key) ? 'true' : 'false';
        } elseif (env($key) === null) {
            $old = 'null';
        } else {
            $old = env($key);
        }

        return $old;
    }
}
