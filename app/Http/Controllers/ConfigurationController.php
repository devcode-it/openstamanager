<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfigurationController extends Controller
{
    public static function isConfigCompleted()
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

        return $state;
    }

    public function save(Request $request)
    {
        // Controllo sullo stato della connessione
        $result = $this->checkConnection($request);
        if ($result === null) {
            return redirect(route('configuration'));
        }

        // Individuazione parametri aggiuntivi
        $decimals = $request->input('decimal_separator');
        $thousands = $request->input('thousand_separator');
        $decimals = $decimals == 'dot' ? '.' : ',';
        $thousands = $thousands == 'dot' ? '.' : $thousands;
        $thousands = $thousands == 'comma' ? ',' : $thousands;

        // Completamento configurazione
        $pairs = [
            'APP_LOCALE' => $request->input('language'),

            'DB_HOST' => $request->input('host'),
            'DB_USERNAME' => $request->input('username'),
            'DB_PASSWORD' => $request->input('password'),
            'DB_DATABASE' => $request->input('database_name'),

            /*
            '|timestamp|' => post('timestamp_format'),
            '|date|' => post('date_format'),
            '|time|' => post('time_format'),
            '|decimals|' => $decimals,
            '|thousands|' => $thousands,
            */
        ];

        foreach ($pairs as $key => $value) {
            $this->updateEnv($key, $value);
        }

        return redirect(route('configuration'));
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

    protected function updateEnv($key, $value)
    {
        $path = base_path('.env');

        if (is_bool(env($key))) {
            $old = env($key) ? 'true' : 'false';
        } elseif (env($key) === null) {
            $old = 'null';
        } else {
            $old = env($key);
        }

        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                "$key=".$old,
                "$key=".$value,
                file_get_contents($path)
            ));
        }
    }
}
