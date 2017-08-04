<?php

/**
 * Classe per la gestione delle utenze.
 *
 * @since 2.3
 */
class Auth
{
    protected static $infos;
    protected static $first_module = null;

    public function __construct()
    {
        $database = Database::getConnection();

        if (API::isAPIRequest()) {
            $this->api(filter('token'));
        }
        // Controllo sulla sessione attiva
        elseif (!empty($_SESSION['idutente']) && $database->isConnected() && $database->isInstalled()) {
            $this->find();
        }
    }

    public function attempt($username, $password)
    {
        session_regenerate_id();

        $database = Database::getConnection();

        $users = $database->fetchArray('SELECT idutente, username, password, enabled FROM zz_users WHERE username = '.prepare($username).' LIMIT 1');

        $log = [];
        $log['username'] = $username;
        $log['ip'] = get_client_ip();
        $log['stato'] = 0;

        if (!empty($users)) {
            $user = $users[0];

            if (empty($user['enabled'])) {
                $log['stato'] = 2;
            } else {
                $_SESSION['idutente'] = $user['idutente'];

                $continue = $this->password_check($password, $user['password']) && $this->find();
                if (!$continue || empty(self::$first_module)) {
                    if ($continue && empty(self::$first_module)) {
                        $log['stato'] = 3;
                    }

                    self::logout();
                } else {
                    $log['idutente'] = self::$infos['idutente'];
                    $log['stato'] = 1;
                }
            }
        }

        $messages = [
            0 => _('Autenticazione fallita!'),
            2 => _('Utente non abilitato!'),
            3 => _("L'utente non ha nessun permesso impostato!"),
        ];
        if (!empty($messages[$log['stato']])) {
            $_SESSION['errors'][] = $messages[$log['stato']];
        }

        foreach ($log as $key => $value) {
            $log[$key] = prepare($value);
        }
        $database->query('INSERT INTO zz_logs('.implode(', ', array_keys($log)).') VALUES('.implode(', ', $log).')');

        return self::check();
    }

    protected function password_check($password, $hash)
    {
        if ($hash == md5($password)) {
            $database = Database::getConnection();

            $database->query('UPDATE zz_users SET password='.prepare(self::hashPassword($password)).' WHERE idutente = '.prepare($_SESSION['idutente']));

            return true;
        }

        if (password_verify($password, $hash)) {
            return true;
        }

        return false;
    }

    /**
     * Crea la l'hash della password per il successivo salvataggio all'interno del database.
     *
     * @since 2.3
     *
     * @return string
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    protected function find()
    {
        $database = Database::getConnection();

        $user = self::userInfo($_SESSION['idutente']);

        if (!empty($user)) {
            foreach ($user as $key => $value) {
                $_SESSION[$key] = $value;
            }

            self::$infos = $user;

            $query = 'SELECT id FROM zz_modules WHERE enabled = 1';
            if (!self::isAdmin()) {
                $query .= ' AND id IN (SELECT idmodule FROM zz_permissions WHERE idgruppo = (SELECT id FROM zz_groups WHERE nome = '.prepare($_SESSION['gruppo']).") AND permessi IN ('r', 'rw'))";
            }

            $results = $database->fetchArray($query.' ORDER BY `order` ASC');

            if (!empty($results)) {
                $module = null;

                $first = get_var('Prima pagina');
                if (array_search($first, array_column($results, 'id')) === false) {
                    foreach ($results as $result) {
                        if (!empty($result['options']) && $result['options'] != 'menu') {
                            $module = $result['id'];
                            break;
                        }
                    }
                } else {
                    $module = $first;
                }

                self::$first_module = $module;
            }

            $identifier = md5($_SESSION['idutente'].$_SERVER['HTTP_USER_AGENT']);
            if ((empty($_SESSION['last_active']) || time() < $_SESSION['last_active'] + (60 * 60)) && (empty($_SESSION['identifier']) || $_SESSION['identifier'] == $identifier)) {
                $_SESSION['last_active'] = time();
                $_SESSION['identifier'] = $identifier;

                return true;
            }
        }

        self::logout();

        return false;
    }

    protected static function userInfo($user_id)
    {
        $database = Database::getConnection();

        $results = $database->fetchArray('SELECT *, (SELECT nome FROM zz_groups WHERE id=idgruppo) AS gruppo FROM zz_users WHERE idutente = '.prepare($user_id).' AND enabled = 1 LIMIT 1');

        $infos = [];

        if (!empty($results)) {
            $infos['idutente'] = $results[0]['idutente'];
            $infos['is_admin'] = ($results[0]['gruppo'] == 'Amministratori');
            $infos['idanagrafica'] = $results[0]['idanagrafica'];
            $infos['username'] = $results[0]['username'];
            $infos['gruppo'] = $results[0]['gruppo'];
        }

        return $infos;
    }

    /**
     * Controlla se la chiave di accesso per l'API abilita l'accesso di un utente.
     *
     * @param string $token Chiave
     *
     * @return array
     */
    public function api($token)
    {
        $database = Database::getConnection();

        $results = $database->fetchArray('SELECT `id_utente` FROM `zz_tokens` WHERE `token` = '.prepare($token));

        if (!empty($results)) {
            self::$infos = self::userInfo($results[0]['id_utente']);
        }

        return self::$infos;
    }

    public static function check()
    {
        return !empty(self::$infos);
    }

    public static function isAdmin()
    {
        return self::check() && !empty(self::$infos['is_admin']);
    }

    public static function logout()
    {
        if (self::check() || !empty($_SESSION['idutente'])) {
            self::$infos = null;
            self::$first_module = null;

            session_unset();
            session_destroy();
            session_start();
            session_regenerate_id();

            $_SESSION['infos'] = [];
            $_SESSION['warnings'] = [];
            $_SESSION['errors'] = [];
        }
    }

    public static function getFirstModule()
    {
        return self::$first_module;
    }

    public static function getUser()
    {
        return self::$infos;
    }
}
