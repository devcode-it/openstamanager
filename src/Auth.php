<?php

/**
 * Classe per la gestione delle utenze.
 *
 * @since 2.3
 */
class Auth extends \Util\Singleton
{
    /** @var array Stati previsti dal sistema di autenticazione */
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

    /** @var array Opzioni di sicurezza relative all'hashing delle password */
    protected static $passwordOptions = [
        'algorithm' => PASSWORD_BCRYPT,
        'options' => [],
    ];

    /** @var int Opzioni per la protezione contro attacchi brute-force */
    protected static $brute = [
        'attemps' => 3,
        'timeout' => 180,
    ];
    /** @var bool Informazioni riguardanti la condizione brute-force */
    protected static $is_brute;

    /** @var array Informazioni riguardanti l'utente autenticato */
    protected $infos;
    /** @var string Nome del primo modulo su cui l'utente ha permessi di navigazione */
    protected $first_module;

    protected function __construct()
    {
        $database = Database::getConnection();

        if ($database->isInstalled()) {
            // Controllo dell'accesso da API
            if (API::isAPIRequest()) {
                $token = API::getRequest()['token'];

                $user = $database->fetchArray('SELECT `id_utente` FROM `zz_tokens` WHERE `enabled` = 1 AND `token` = '.prepare($token));

                $id = !empty($user) ? $user[0]['id_utente'] : null;
            }
            // Controllo sulla sessione attiva
            elseif (!empty($_SESSION['id_utente'])) {
                $id = $_SESSION['id_utente'];
            }

            if (!empty($id)) {
                $this->identifyUser($id);
            }

            $this->saveToSession();
        }
    }

    /**
     * Effettua un tentativo di accesso con le credenziali fornite.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function attempt($username, $password)
    {
        session_regenerate_id();

        // Controllo sulla disponibilità dell'accesso (brute-forcing non in corso)
        if (self::isBrute()) {
            return false;
        }

        $database = Database::getConnection();

        $log = [];
        $log['username'] = (string) $username;
        $log['ip'] = get_client_ip();
        $log['stato'] = self::$status['failed']['code'];

        $users = $database->fetchArray('SELECT id AS id_utente, password, enabled FROM zz_users WHERE username = '.prepare($username).' LIMIT 1');
        if (!empty($users)) {
            $user = $users[0];

            if (!empty($user['enabled'])) {
                $this->identifyUser($user['id_utente']);
                $module = $this->getFirstModule();

                if (
                    $this->isAuthenticated() &&
                    $this->password_check($password, $user['password'], $user['id_utente']) &&
                    !empty($module)
                ) {
                    // Accesso completato
                    $log['id_utente'] = $this->infos['id_utente'];
                    $log['stato'] = self::$status['success']['code'];

                    // Salvataggio nella sessione
                    $this->saveToSession();
                } else {
                    if (empty($module)) {
                        $log['stato'] = self::$status['unauthorized']['code'];
                    }

                    // Logout automatico
                    $this->destory();
                }
            } else {
                $log['stato'] = self::$status['disabled']['code'];
            }
        }

        // Salvataggio dello stato nella sessione
        if ($log['stato'] != self::$status['success']['code']) {
            foreach (self::$status as $key => $value) {
                if ($log['stato'] == $value['code']) {
                    $_SESSION['errors'][] = $value['message'];
                    break;
                }
            }
        }

        // Salvataggio del tentativo nel database
        $database->insert('zz_logs', $log);

        return $this->isAuthenticated();
    }

    /**
     * Controlla la corrispondeza delle password ed eventalmente effettua un rehashing.
     *
     * @param string $password
     * @param string $hash
     * @param int    $user_id
     */
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
            $database->update('zz_users', ['password' => self::hashPassword($password)], ['id' => $user_id]);
        }

        return $result;
    }

    /**
     * Memorizza le informazioni riguardanti l'utente all'interno della sessione.
     */
    protected function saveToSession()
    {
        if (session_status() == PHP_SESSION_ACTIVE && $this->isAuthenticated()) {
            foreach ($this->infos as $key => $value) {
                $_SESSION[$key] = $value;
            }

            $identifier = md5($_SESSION['id_utente'].$_SERVER['HTTP_USER_AGENT']);
            if ((empty($_SESSION['last_active']) || time() < $_SESSION['last_active'] + (60 * 60)) && (empty($_SESSION['identifier']) || $_SESSION['identifier'] == $identifier)) {
                $_SESSION['last_active'] = time();
                $_SESSION['identifier'] = $identifier;
            }
        }
    }

    /**
     * Identifica l'utente interessato dall'autenticazione.
     *
     * @param int $user_id
     */
    protected function identifyUser($user_id)
    {
        $database = Database::getConnection();

        try {
            $results = $database->fetchArray('SELECT id AS id_utente, idanagrafica, username, (SELECT nome FROM zz_groups WHERE zz_groups.id=zz_users.idgruppo) AS gruppo FROM zz_users WHERE id = '.prepare($user_id).' AND enabled = 1 LIMIT 1', false, ['session' => false]);

            if (!empty($results)) {
                $results[0]['id'] = $results[0]['id_utente'];
                $results[0]['is_admin'] = ($results[0]['gruppo'] == 'Amministratori');

                $this->infos = $results[0];
            }
        } catch (PDOException $e) {
            $this->destory();
        }
    }

    /**
     * Controlla se l'utente è autenticato.
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return !empty($this->infos);
    }

    /**
     * Controlla se l'utente appartiene al gruppo degli Amministratori.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->isAuthenticated() && !empty($this->infos['is_admin']);
    }

    /**
     * Restituisce le informazioni riguardanti l'utente autenticato.
     *
     * @return array
     */
    public function getUser()
    {
        return $this->infos;
    }

    /**
     * Restituisce il token di accesso all'API per l'utente autenticato.
     *
     * @return string
     */
    public function getToken()
    {
        $token = null;

        if ($this->isAuthenticated()) {
            $user = self::user();

            $database = Database::getConnection();
            $tokens = $database->fetchArray('SELECT `token` FROM `zz_tokens` WHERE `enabled` = 1 AND `id_utente` = '.prepare($user['id_utente']));

            // Generazione del token per l'utente
            if (empty($tokens)) {
                $token = secure_random_string();

                $database->insert('zz_tokens', [
                    'id_utente' => $user['id_utente'],
                    'token' => $token,
                ]);
            } else {
                $token = $tokens[0]['token'];
            }
        }

        return $token;
    }

    /**
     * Distrugge le informazioni riguardanti l'utente autenticato, forzando il logout.
     */
    public function destory()
    {
        if ($this->isAuthenticated() || !empty($_SESSION['id_utente'])) {
            $this->infos = null;
            $this->first_module = null;

            session_unset();
            session_regenerate_id();

            $_SESSION['infos'] = [];
            $_SESSION['warnings'] = [];
            $_SESSION['errors'] = [];
        }
    }

    /**
     * Restituisce il nome del primo modulo navigabile dall'utente autenticato.
     *
     * @return string
     */
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

    /**
     * Restituisce l'hashing della password per la relativa memorizzazione nel database.
     *
     * @param string $password
     *
     * @return string
     */
    public static function hashPassword($password)
    {
        return password_hash($password, self::$passwordOptions['algorithm'], self::$passwordOptions['options']);
    }

    /**
     * Restituisce l'elenco degli stati del sistema di autenticazione.
     *
     * @return array
     */
    public static function getStatus()
    {
        return self::$status;
    }

    /**
     * Controlla se l'utente è autenticato.
     *
     * @return bool
     */
    public static function check()
    {
        return self::getInstance()->isAuthenticated();
    }

    /**
     * Controlla se l'utente appartiene al gruppo degli Amministratori.
     *
     * @return bool
     */
    public static function admin()
    {
        return self::getInstance()->isAdmin();
    }

    /**
     * Restituisce le informazioni riguardanti l'utente autenticato.
     *
     * @return array
     */
    public static function user()
    {
        return self::getInstance()->getUser();
    }

    /**
     * Distrugge le informazioni riguardanti l'utente autenticato, forzando il logout.
     */
    public static function logout()
    {
        return self::getInstance()->destory();
    }

    /**
     * Restituisce il nome del primo modulo navigabile dall'utente autenticato.
     *
     * @return string
     */
    public static function firstModule()
    {
        return self::getInstance()->getFirstModule();
    }

    /**
     * Controlla se sono in corso molti tentativi di accesso (possibile brute-forcing in corso).
     *
     * @return bool
     */
    public static function isBrute()
    {
        $database = Database::getConnection();

        if (!$database->isInstalled() || !$database->fetchNum("SHOW TABLES LIKE 'zz_logs'") || Update::isUpdateAvailable()) {
            return false;
        }

        if (!isset(self::$is_brute)) {
            $results = $database->fetchArray('SELECT COUNT(*) AS tot FROM zz_logs WHERE ip = '.prepare(get_client_ip()).' AND stato = '.prepare(self::getStatus()['failed']['code']).' AND DATE_ADD(created_at, INTERVAL '.self::$brute['timeout'].' SECOND) >= NOW()');

            self::$is_brute = $results[0]['tot'] > self::$brute['attemps'];
        }

        return self::$is_brute;
    }

    /**
     * Restituisce il tempo di attesa rimanente per lo sblocco automatico dellla protezione contro attacchi brute-force.
     *
     * @return int
     */
    public static function getBruteTimeout()
    {
        if (!self::isBrute()) {
            return 0;
        }

        $database = Database::getConnection();

        $results = $database->fetchArray('SELECT TIME_TO_SEC(TIMEDIFF(DATE_ADD(created_at, INTERVAL '.self::$brute['timeout'].' SECOND), NOW())) AS diff FROM zz_logs WHERE ip = '.prepare(get_client_ip()).' AND stato = '.prepare(self::getStatus()['failed']['code']).' AND DATE_ADD(created_at, INTERVAL '.self::$brute['timeout'].' SECOND) >= NOW() ORDER BY created_at DESC LIMIT 1');

        return intval($results[0]['diff']);
    }
}
