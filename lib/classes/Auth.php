<?php

/**
 * Classe per la gestione delle utenze.
 *
 * @since 2.3
 */
class Auth extends \Util\Singleton
{
    protected static $status = [
        'success' => [
            'code' => 1,
            'message' => 'Login riuscito!',
        ],
        'failed' => [
            'code' => 0,
            'message' => 'Autenticazione fallita!',
        ],
        'disabled' => [
            'code' => 2,
            'message' => 'Utente non abilitato!',
        ],
        'unauthorized' => [
            'code' => 3,
            'message' => "L'utente non ha nessun permesso impostato!",
        ],
    ];

    protected static $passwordOptions = [
        'algorithm' => PASSWORD_BCRYPT,
        'options' => [],
    ];

    protected $infos;
    protected $first_module;

    protected function __construct()
    {
        $database = Database::getConnection();

        if ($database->isInstalled()) {
            if (API::isAPIRequest()) {
                $token = filter('token');

                $id = $database->fetchArray('SELECT `id_utente` FROM `zz_tokens` WHERE `token` = '.prepare($token))[0]['id_utente'];
            }
            // Controllo sulla sessione attiva
            elseif (!empty($_SESSION['idutente'])) {
                $id = $_SESSION['idutente'];
            }

            if (!empty($id)) {
                $this->identifyUser($id);
            }

            if (!empty($_SESSION['idutente']) && $this->isAuthenticated()) {
                $this->saveToSession();
            }
        }
    }

    public function attempt($username, $password)
    {
        session_regenerate_id();

        $database = Database::getConnection();

        $log = [];
        $log['username'] = $username;
        $log['ip'] = get_client_ip();
        $log['stato'] = self::$status['failed']['code'];

        $users = $database->fetchArray('SELECT idutente, password, enabled FROM zz_users WHERE username = '.prepare($username).' LIMIT 1');
        if (!empty($users)) {
            $user = $users[0];

            if (!empty($user['enabled'])) {
                $this->identifyUser($user['idutente']);
                $module = $this->getFirstModule();

                if (
                    $this->isAuthenticated() &&
                    $this->password_check($password, $user['password'], $user['idutente']) &&
                    !empty($module)
                ) {
                    $log['idutente'] = $this->infos['idutente'];
                    $log['stato'] = self::$status['success']['code'];

                    $this->saveToSession();
                } else {
                    if (empty($module)) {
                        $log['stato'] = self::$status['unauthorized']['code'];
                    }

                    $this->logout();
                }
            } else {
                $log['stato'] = self::$status['disabled']['code'];
            }
        }

        if ($log['stato'] != self::$status['success']['code']) {
            foreach (self::$status as $key => $value) {
                if ($log['stato'] == $value['code']) {
                    $_SESSION['errors'][] = $value['message'];
                    break;
                }
            }
        }

        $database->insert('zz_logs', $log);

        return $this->isAuthenticated();
    }

    protected function password_check($password, $hash, $user_id = null)
    {
        $result = false;

        // Retrocompatibilità
        if ($hash == md5($password)) {
            $rehash = true;

            $result = true;
        }

        // Nuova versione
        if (password_verify($password, $hash)) {
            $rehash = password_needs_rehash($hash, self::$passwordOptions['algorithm'], self::$passwordOptions['options']);

            $result = true;
        }

        // Controllo in automatico per futuri cambiamenti dell'algoritmo di password
        if ($rehash) {
            $database = Database::getConnection();
            $database->update('zz_users', ['password' => self::hashPassword($password)], ['idutente' => $user_id]);
        }

        return $result;
    }

    protected function saveToSession()
    {
        foreach ($this->infos as $key => $value) {
            $_SESSION[$key] = $value;
        }

        $identifier = md5($_SESSION['idutente'].$_SERVER['HTTP_USER_AGENT']);
        if ((empty($_SESSION['last_active']) || time() < $_SESSION['last_active'] + (60 * 60)) && (empty($_SESSION['identifier']) || $_SESSION['identifier'] == $identifier)) {
            $_SESSION['last_active'] = time();
            $_SESSION['identifier'] = $identifier;
        }
    }

    protected function identifyUser($user_id)
    {
        $database = Database::getConnection();

        $results = $database->fetchArray('SELECT idutente, idanagrafica, username, (SELECT nome FROM zz_groups WHERE id=idgruppo) AS gruppo FROM zz_users WHERE idutente = '.prepare($user_id).' AND enabled = 1 LIMIT 1');
        if (!empty($results)) {
            $results[0]['is_admin'] = ($results[0]['gruppo'] == 'Amministratori');

            $this->infos = $results[0];
        }
    }

    public function isAuthenticated()
    {
        return !empty($this->infos);
    }

    public function isAdmin()
    {
        return $this->isAuthenticated() && !empty($this->infos['is_admin']);
    }

    public function getUser()
    {
        return $this->infos;
    }

    public function destory()
    {
        if ($this->isAuthenticated() || !empty($_SESSION['idutente'])) {
            $this->infos = null;
            $this->first_module = null;

            session_unset();
            session_destroy();
            session_start();
            session_regenerate_id();

            $_SESSION['infos'] = [];
            $_SESSION['warnings'] = [];
            $_SESSION['errors'] = [];
        }
    }

    public function getFirstModule()
    {
        if (empty($this->first_module)) {
            $query = 'SELECT id FROM zz_modules WHERE enabled = 1';
            if (!$this->isAdmin()) {
                $query .= ' AND id IN (SELECT idmodule FROM zz_permissions WHERE idgruppo = (SELECT id FROM zz_groups WHERE nome = '.prepare($this->getUser()['gruppo']).") AND permessi IN ('r', 'rw'))";
            }

            $database = Database::getConnection();
            $results = $database->fetchArray($query." AND options != '' AND options != 'menu' AND options IS NOT NULL ORDER BY `order` ASC");

            if (!empty($results)) {
                $module = null;

                $first = Settings::get('Prima pagina');
                if (!in_array($first, array_column($results, 'id'))) {
                    $module = $results[0]['id'];
                } else {
                    $module = $first;
                }

                $this->first_module = $module;
            }
        }

        return $this->first_module;
    }

    public static function hashPassword($password)
    {
        return password_hash($password, self::$passwordOptions['algorithm'], self::$passwordOptions['options']);
    }

    public static function check()
    {
        return self::getInstance()->isAuthenticated();
    }

    public static function admin()
    {
        return self::getInstance()->isAdmin();
    }

    public static function user()
    {
        return self::getInstance()->getUser();
    }

    public static function logout()
    {
        return self::getInstance()->destory();
    }

    public static function firstModule()
    {
        return self::getInstance()->getFirstModule();
    }

    public static function getStatus()
    {
        return self::$status;
    }
}
