<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use API\Response as API;
use Models\Group;
use Models\User;

/**
 * Classe per la gestione delle utenze.
 *
 * @since 2.3
 */
class AuthOSM extends Util\Singleton
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
            'code' => 5,
            'message' => "L'utente non ha nessun permesso impostato!",
        ],
        'already_logged_in' => [
            'code' => 6,
            'message' => 'Questo utente è già connesso al gestionale. Chiudere la sessione precedente o riprovare più tardi.',
        ],
    ];

    /** @var array Opzioni di sicurezza relative all'hashing delle password */
    protected static $password_options = [
        'algorithm' => PASSWORD_BCRYPT,
        'options' => [],
    ];

    /** @var array Opzioni per la protezione contro attacchi brute-force */
    protected static $brute_options = [
        'attemps' => 3,
        'timeout' => 180,
    ];
    /** @var bool Informazioni riguardanti la condizione brute-force */
    protected static $is_brute;

    /** @var array Informazioni riguardanti l'utente autenticato */
    protected $user;
    /** @var array|null Informazioni riguardanti il token di accesso */
    protected $token_user;
    /** @var string Stato del tentativo di accesso */
    protected $current_status;
    /** @var string|null Nome del primo modulo su cui l'utente ha permessi di navigazione */
    protected $first_module;

    protected function __construct()
    {
        $database = database();

        if ($database->isInstalled()) {
            // Controllo dell'accesso da API
            if (API::isAPIRequest()) {
                $token = API::getRequest()['token'];

                $user = $database->fetchArray('SELECT `id_utente` FROM `zz_tokens` WHERE `enabled` = 1 AND `token` = :token', [
                    ':token' => $token,
                ]);

                $id = !empty($user) ? $user[0]['id_utente'] : null;
            }
            // Controllo sulla sessione attiva
            elseif (!empty($_SESSION['id_utente'])) {
                $id = $_SESSION['id_utente'];
            }

            if (!empty($id) && database()->columnExists('zz_users', 'session_token')) {
                $this->identifyUser($id);
            }

            // Carica il token dalla sessione se presente
            if (!empty($_SESSION['token_user']) && !empty($_SESSION['token_access'])) {
                $this->loadTokenFromSession();
            }

            $this->saveToSession();
        }
    }

    /**
     * Effettua un tentativo di accesso con le credenziali fornite.
     *
     * @param string $username
     * @param string $password
     * @param bool   $force    Forza il login solo tramite username (serve per l'autenticazione con Oauth2)
     *
     * @return bool
     */
    public function attempt($username, $password, $force = false)
    {
        session_regenerate_id();

        // Controllo sulla disponibilità dell'accesso (brute-forcing non in corso)
        if (self::isBrute()) {
            return false;
        }

        $database = database();

        $log = [];
        $log['username'] = $username;
        $log['ip'] = get_client_ip();

        $status = 'failed';

        $users = $database->fetchArray('SELECT id, password, enabled, session_token FROM zz_users WHERE username = :username LIMIT 1', [
            ':username' => $username,
        ]);
        if (!empty($users)) {
            $user = $users[0];

            // Verifica se l'utente è già connesso (ha un token di sessione attivo)
            if (!empty($user['session_token'])) {
                // Verifica se ci sono operazioni recenti per l'utente (sessione attiva)
                $session_timeout = 10; // minuti
                
                $recent_operations = $database->fetchArray('SELECT COUNT(*) as count FROM zz_operations
                    WHERE id_utente = :user_id
                    AND DATE_ADD(created_at, INTERVAL :timeout MINUTE) >= NOW()', [
                    ':user_id' => $user['id'],
                    ':timeout' => $session_timeout,
                ]);
                
                // Se ci sono operazioni recenti, la sessione è ancora attiva -> blocca il login
                if (!empty($recent_operations) && $recent_operations[0]['count'] > 0) {
                    $status = 'already_logged_in';
                    $this->current_status = $status;

                    // Log del tentativo
                    $log['stato'] = self::getStatus()[$status]['code'];
                    $log['user_agent'] = Filter::getPurifier()->purify($_SERVER['HTTP_USER_AGENT']);
                    $database->insert('zz_logs', $log);

                    return false;
                }
                
                // Se non ci sono operazioni recenti, la sessione è scaduta -> resetta il token e permetti il login
                $database->update('zz_users', [
                    'session_token' => null,
                ], [
                    'id' => $user['id'],
                ]);
            }

            if (!empty($user['enabled'])) {
                $this->identifyUser($user['id']);
                $gruppo = Group::join('zz_users', 'zz_users.idgruppo', '=', 'zz_groups.id')->where('zz_users.id', '=', $user['id'])->first();
                $module = $gruppo->id_module_start;
                $module = $this->getFirstModule($module);

                if ($force) {
                    // Accesso completato
                    $log['id_utente'] = $this->user->id;
                    $status = 'success';

                    // Genera e salva il token di sessione
                    $this->generateSessionToken($this->user->id);

                    // Salvataggio nella sessione
                    $this->saveToSession();
                } elseif (
                    $this->password_check($password, $user['password'], $user['id'])
                    && !empty($module)
                ) {
                    // Accesso completato
                    $log['id_utente'] = $this->user->id;
                    $status = 'success';

                    // Genera e salva il token di sessione
                    $this->generateSessionToken($this->user->id);

                    // Salvataggio nella sessione
                    $this->saveToSession();
                } else {
                    if (empty($module)) {
                        $status = 'unauthorized';
                    }

                    // Logout automatico
                    $this->destory();
                }
            } else {
                $status = 'disabled';
            }
        }

        // Salvataggio dello stato corrente
        $log['stato'] = self::getStatus()[$status]['code'];
        $log['user_agent'] = Filter::getPurifier()->purify($_SERVER['HTTP_USER_AGENT']);
        $this->current_status = $status;

        // Salvataggio del tentativo nel database
        $database->insert('zz_logs', $log);

        return $this->isAuthenticated();
    }

    /**
     * Controlla se l'utente è autenticato.
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        // Controllo autenticazione normale
        if (!empty($this->user)) {
            // Verifica il token di sessione
            if (!$this->checkSessionToken()) {
                return false;
            }

            return true;
        }

        // Controllo autenticazione tramite token
        if (!empty($this->token_user)) {
            // Verifica che il token sia ancora valido
            return $this->isTokenStillValid();
        }

        // Retrocompatibilità: controlla anche la sessione
        if (!empty($_SESSION['token_user']) && !empty($_SESSION['token_access'])) {
            // Carica il token dalla sessione se non è già caricato nella classe
            $this->loadTokenFromSession();

            return $this->isTokenStillValid();
        }

        return false;
    }

    /**
     * Controlla se l'utente appartiene al gruppo degli Amministratori.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->isAuthenticated() && !empty($this->user->is_admin);
    }

    /**
     * Restituisce le informazioni riguardanti l'utente autenticato.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Restituisce lo stato corrente.
     *
     * @return string
     */
    public function getCurrentStatus()
    {
        return $this->current_status;
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

            $tokens = $user->getApiTokens();
            $token = $tokens[0]['token'];
        }

        return $token;
    }

    /**
     * Distrugge le informazioni riguardanti l'utente autenticato, forzando il logout.
     */
    public function destory()
    {
        if ($this->isAuthenticated() || !empty($_SESSION['id_utente'])) {
            // Pulisci il token di sessione dal database
            if (!empty($this->user) && !empty($this->user->id)) {
                $database = database();
                $database->update('zz_users', [
                    'session_token' => null,
                ], [
                    'id' => $this->user->id,
                ]);
            } elseif (!empty($_SESSION['id_utente'])) {
                $database = database();
                $database->update('zz_users', [
                    'session_token' => null,
                ], [
                    'id' => $_SESSION['id_utente'],
                ]);
            }

            $this->user = [];
            $this->token_user = null;
            $this->first_module = null;

            session_unset();
            session_regenerate_id();

            if (!API::isAPIRequest()) {
                flash()->clearMessages();
            }
        }
    }

    /**
     * Restituisce il nome del primo modulo navigabile dall'utente autenticato.
     *
     * @return string|null
     */
    public function getFirstModule($first = null)
    {
        if (empty($this->first_module)) {
            $parameters = [];

            $query = 'SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `enabled` = 1';
            if (!$this->isAdmin()) {
                $group = $this->getUser()['gruppo'];

                $query .= ' AND `id` IN (SELECT `idmodule` FROM `zz_permissions` WHERE `idgruppo` = '.Group::where('nome', $group)->first()->id." AND `permessi` IN ('r', 'rw'))";
            }

            $database = database();
            $results = $database->fetchArray($query." AND `options` != '' AND `options` != 'menu' AND `options` IS NOT NULL ORDER BY `order` ASC", $parameters);

            if (!empty($results)) {
                $module = null;

                if (empty($first)) {
                    $first = setting('Prima pagina');
                }
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
     * @return string|bool
     */
    public static function hashPassword($password)
    {
        return password_hash($password, self::$password_options['algorithm'], self::$password_options['options']);
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
     * @return User
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
        $database = database();

        if (!$database->isInstalled() || !$database->tableExists('zz_logs') || Update::isUpdateAvailable()) {
            return false;
        }

        if (!isset(self::$is_brute)) {
            $results = $database->fetchArray('SELECT COUNT(*) AS tot FROM zz_logs WHERE ip = :ip AND stato = :state AND DATE_ADD(created_at, INTERVAL :timeout SECOND) >= NOW()', [
                ':ip' => get_client_ip(),
                ':state' => self::getStatus()['failed']['code'],
                ':timeout' => self::$brute_options['timeout'],
            ]);

            self::$is_brute = $results[0]['tot'] > self::$brute_options['attemps'];
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

        $database = database();

        $results = $database->fetchArray('SELECT TIME_TO_SEC(TIMEDIFF(DATE_ADD(created_at, INTERVAL '.self::$brute_options['timeout'].' SECOND), NOW())) AS diff FROM zz_logs WHERE ip = :ip AND stato = :state AND DATE_ADD(created_at, INTERVAL :timeout SECOND) >= NOW() ORDER BY created_at DESC LIMIT 1', [
            ':ip' => get_client_ip(),
            ':state' => self::getStatus()['failed']['code'],
            ':timeout' => self::$brute_options['timeout'],
        ]);

        return intval($results[0]['diff']);
    }

    /**
     * Genera un token OTP sicuro per l'utente.
     *
     * Genera un codice OTP alfanumerico di 6 caratteri utilizzando caratteri
     * facilmente distinguibili per evitare confusione durante l'inserimento.
     * Esclude caratteri ambigui come 0, O, 1, I, l per migliorare l'usabilità.
     *
     * @param int $length Lunghezza del codice OTP (default: 6)
     *
     * @return string Codice OTP generato
     */
    public function getOTP($length = 6)
    {
        // Caratteri utilizzabili per l'OTP (esclusi caratteri ambigui)
        // Esclusi: 0 (zero), O (o maiuscola), 1 (uno), I (i maiuscola), l (L minuscola)
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $otp = '';

        // Genera il codice OTP carattere per carattere
        for ($i = 0; $i < $length; ++$i) {
            // Utilizza random_int per una generazione crittograficamente sicura
            $randomIndex = random_int(0, $charactersLength - 1);
            $otp .= $characters[$randomIndex];
        }

        return $otp;
    }

    /**
     * Valida un codice OTP.
     *
     * Verifica che il codice OTP fornito sia nel formato corretto:
     * - Lunghezza esatta di 6 caratteri
     * - Solo caratteri alfanumerici maiuscoli
     * - Esclude caratteri ambigui (0, O, 1, I, l)
     *
     * @param string $otp Codice OTP da validare
     *
     * @return bool True se il codice è valido, false altrimenti
     */
    public function validateOTP($otp)
    {
        // Verifica lunghezza
        if (strlen($otp) !== 6) {
            return false;
        }

        // Verifica che contenga solo caratteri validi
        $validCharacters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        for ($i = 0; $i < strlen($otp); ++$i) {
            if (!str_contains($validCharacters, $otp[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Normalizza un codice OTP rimuovendo spazi e convertendo in maiuscolo.
     *
     * @param string $otp Codice OTP da normalizzare
     *
     * @return string Codice OTP normalizzato
     */
    public function normalizeOTP($otp)
    {
        // Rimuove spazi e converte in maiuscolo
        $normalized = strtoupper(trim($otp));

        // Rimuove caratteri non alfanumerici
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized);

        return $normalized;
    }

    /**
     * Effettua il login tramite token OTP.
     *
     * Verifica la validità del token, del codice OTP e effettua l'autenticazione
     * dell'utente associato. Gestisce anche la pulizia del codice OTP utilizzato
     * e il logging dell'accesso.
     *
     * @param string $token    Token di accesso
     * @param string $otp_code Codice OTP inserito dall'utente
     *
     * @return array Risultato dell'operazione con status e messaggio
     */
    public function attemptOTPLogin($token, $otp_code)
    {
        $database = database();

        // Normalizza il codice OTP
        $otp_code = $this->normalizeOTP($otp_code);

        // Verifica formato OTP
        if (!$this->validateOTP($otp_code)) {
            return [
                'success' => false,
                'status' => 'invalid_otp_format',
                'message' => tr('Formato codice OTP non valido'),
            ];
        }

        // Verifica token e OTP nel database
        $token_record = $database->fetchOne('SELECT * FROM `zz_otp_tokens` WHERE `token` = '.prepare($token).' AND `enabled` = 1');

        if (empty($token_record)) {
            return [
                'success' => false,
                'status' => 'invalid_token',
                'message' => tr('Token non valido o OTP non abilitato'),
            ];
        }

        // Verifica se il token ha delle date impostate e se è attivo
        $is_not_active = $this->checkTokenValidity($token_record);
        if ($is_not_active) {
            return [
                'success' => false,
                'status' => 'token_not_active',
                'message' => tr('Token non attivo'),
            ];
        }

        // Verifica corrispondenza OTP
        if ($token_record['last_otp'] !== $otp_code || empty($otp_code)) {
            return [
                'success' => false,
                'status' => 'invalid_otp',
                'message' => tr('Codice OTP non valido o scaduto'),
            ];
        }

        // Effettua il login
        session_regenerate_id();

        $utente = null;

        // Se il token ha un utente associato, usa l'autenticazione normale
        if (!empty($token_record['id_utente'])) {
            $utente = User::find($token_record['id_utente']);
            if (!$utente || !$utente->enabled) {
                return [
                    'success' => false,
                    'status' => 'user_disabled',
                    'message' => tr('Utente non abilitato'),
                ];
            }

            $this->identifyUser($utente->id);

            if (!$this->isAuthenticated()) {
                return [
                    'success' => false,
                    'status' => 'auth_failed',
                    'message' => tr('Errore durante l\'autenticazione'),
                ];
            }

            // Verifica permessi modulo per utenti normali
            $gruppo = Group::join('zz_users', 'zz_users.idgruppo', '=', 'zz_groups.id')->where('zz_users.id', '=', $utente->id)->first();
            $module = $gruppo->id_module_start;
            $module = $this->getFirstModule($module);

            if (empty($module)) {
                $this->destory();

                return [
                    'success' => false,
                    'status' => 'no_permissions',
                    'message' => tr('Utente senza permessi di accesso'),
                ];
            }
        } else {
            // Se non c'è un utente associato, crea una sessione virtuale basata sul token
            $this->identifyByToken($token_record);

            // Verifica che il token abbia almeno un modulo target
            if (empty($token_record['id_module_target'])) {
                $this->destory();

                return [
                    'success' => false,
                    'status' => 'no_module_target',
                    'message' => tr('Token senza modulo di destinazione'),
                ];
            }
        }

        // Salva nella sessione (solo per utenti normali)
        if ($utente) {
            $this->saveToSession();
        }

        // Salva informazioni del token nella sessione per gestire permessi limitati (solo se non già fatto da identifyByToken)
        if (empty($_SESSION['token_access'])) {
            $_SESSION['token_access'] = [
                'token_id' => $token_record['id'],
                'tipo_accesso' => $token_record['tipo_accesso'],
                'id_module_target' => $token_record['id_module_target'],
                'id_record_target' => $token_record['id_record_target'],
                'permessi' => $token_record['permessi'],
            ];
        }

        // Pulisci l'OTP utilizzato
        $database->query('UPDATE `zz_otp_tokens` SET `last_otp` = "" WHERE `id` = '.prepare($token_record['id']));

        // Pulisci le sessioni OTP
        unset($_SESSION['otp_last_sent_'.$token_record['id']]);

        // Log del login
        $username = $utente ? $utente->username : 'token_'.$token_record['id'];
        $user_id = $utente ? $utente->id : null;

        $database->insert('zz_logs', [
            'username' => $username,
            'ip' => get_client_ip(),
            'stato' => self::getStatus()['success']['code'],
            'id_utente' => $user_id,
            'user_agent' => Filter::getPurifier()->purify($_SERVER['HTTP_USER_AGENT']),
        ]);

        return [
            'success' => true,
            'status' => 'success',
            'message' => tr('Login effettuato con successo'),
            'user' => $utente,
            'token_info' => $token_record,
        ];
    }

    /**
     * Effettua il login tramite token diretto (senza OTP).
     *
     * Verifica la validità del token e effettua l'autenticazione dell'utente associato.
     * Questo metodo è utilizzato per l'accesso diretto tramite token OAuth.
     *
     * @param string $token Token di accesso
     *
     * @return array Risultato dell'operazione con status e messaggio
     */
    public function attemptTokenLogin($token)
    {
        $database = database();

        // Verifica token nel database
        $token_record = $database->fetchOne('SELECT * FROM `zz_otp_tokens` WHERE `token` = '.prepare($token).' AND `enabled` = 1');

        if (empty($token_record)) {
            return [
                'success' => false,
                'status' => 'invalid_token',
                'message' => tr('Token non valido o non abilitato'),
            ];
        }

        // Verifica se il token ha delle date impostate e se è attivo
        $is_not_active = $this->checkTokenValidity($token_record);
        if ($is_not_active) {
            return [
                'success' => false,
                'status' => 'token_not_active',
                'message' => tr('Token non attivo'),
            ];
        }

        // Effettua il login
        session_regenerate_id();

        $utente = null;

        // Se il token ha un utente associato, usa l'autenticazione normale
        if (!empty($token_record['id_utente'])) {
            $utente = User::find($token_record['id_utente']);
            if (!$utente || !$utente->enabled) {
                return [
                    'success' => false,
                    'status' => 'user_disabled',
                    'message' => tr('Utente non abilitato'),
                ];
            }

            $this->identifyUser($utente->id);

            if (!$this->isAuthenticated()) {
                return [
                    'success' => false,
                    'status' => 'auth_failed',
                    'message' => tr('Errore durante l\'autenticazione'),
                ];
            }

            // Verifica permessi modulo (solo se l'utente non è admin)
            if (!$this->isAdmin()) {
                $gruppo = Group::join('zz_users', 'zz_users.idgruppo', '=', 'zz_groups.id')->where('zz_users.id', '=', $utente->id)->first();
                $module = $gruppo->id_module_start;
                $module = $this->getFirstModule($module);

                if (empty($module)) {
                    $this->destory();

                    return [
                        'success' => false,
                        'status' => 'no_permissions',
                        'message' => tr('Utente senza permessi di accesso'),
                    ];
                }
            }
        } else {
            // Se non c'è un utente associato, crea una sessione virtuale basata sul token
            $this->identifyByToken($token_record);

            // Verifica che il token abbia almeno un modulo target
            if (empty($token_record['id_module_target'])) {
                $this->destory();

                return [
                    'success' => false,
                    'status' => 'no_module_target',
                    'message' => tr('Token senza modulo di destinazione'),
                ];
            }
        }

        // Salva nella sessione (solo per utenti normali)
        if ($utente) {
            $this->saveToSession();
        }

        // Salva informazioni del token nella sessione per gestire permessi limitati (solo se non già fatto da identifyByToken)
        if (empty($_SESSION['token_access'])) {
            $_SESSION['token_access'] = [
                'token_id' => $token_record['id'],
                'tipo_accesso' => $token_record['tipo_accesso'],
                'id_module_target' => $token_record['id_module_target'],
                'id_record_target' => $token_record['id_record_target'],
                'permessi' => $token_record['permessi'],
            ];
        }

        // Log del login
        $username = $utente ? $utente->username : 'token_'.$token_record['id'];
        $user_id = $utente ? $utente->id : null;

        $database->insert('zz_logs', [
            'username' => $username,
            'ip' => get_client_ip(),
            'stato' => self::getStatus()['success']['code'],
            'id_utente' => $user_id,
            'user_agent' => Filter::getPurifier()->purify($_SERVER['HTTP_USER_AGENT']),
        ]);

        return [
            'success' => true,
            'status' => 'success',
            'message' => tr('Login effettuato con successo'),
            'user' => $utente,
            'token_info' => $token_record,
        ];
    }

    /**
     * Memorizza l'URL di destinazione nella sessione per il redirect post-login.
     *
     * @param string $url URL di destinazione
     */
    public function setIntendedUrl($url)
    {
        if ($this->isValidInternalUrl($url)) {
            $_SESSION['intended_url'] = $url;
        }
    }

    /**
     * Recupera l'URL di destinazione dalla sessione.
     *
     * @return string|null
     */
    public function getIntendedUrl()
    {
        return $_SESSION['intended_url'] ?? null;
    }

    /**
     * Verifica se esiste un URL di destinazione memorizzato.
     *
     * @return bool
     */
    public function hasIntendedUrl()
    {
        return !empty($_SESSION['intended_url']);
    }

    /**
     * Pulisce l'URL di destinazione dalla sessione.
     */
    public function clearIntendedUrl()
    {
        unset($_SESSION['intended_url']);
    }

    /**
     * Verifica se l'utente ha i permessi per accedere all'URL intended.
     *
     * @return bool
     */
    public function canAccessIntendedUrl()
    {
        if (!$this->hasIntendedUrl()) {
            return false;
        }

        $url = $this->getIntendedUrl();

        // Estrae l'id_module dall'URL
        if (preg_match('/[?&]id_module=(\d+)/', (string) $url, $matches)) {
            $id_module = $matches[1];

            // Verifica i permessi per il modulo
            $permission = Modules::getPermission($id_module);

            return in_array($permission, ['r', 'rw']);
        }

        return true; // Per URL senza modulo specifico
    }

    /**
     * Metodi statici per l'accesso ai metodi di intended URL.
     */
    public static function setIntended($url)
    {
        return self::getInstance()->setIntendedUrl($url);
    }

    public static function getIntended()
    {
        return self::getInstance()->getIntendedUrl();
    }

    public static function hasIntended()
    {
        return self::getInstance()->hasIntendedUrl();
    }

    public static function clearIntended()
    {
        return self::getInstance()->clearIntendedUrl();
    }

    public static function canAccessIntended()
    {
        return self::getInstance()->canAccessIntendedUrl();
    }

    /**
     * Identifica l'utente interessato dall'autenticazione.
     *
     * @param int $user_id
     */
    public function identifyUser($user_id)
    {
        $database = database();
        
        try {
            $results = $database->fetchArray('SELECT `id`, `idanagrafica`, `username`, `session_token`, (SELECT `title` FROM `zz_groups` LEFT JOIN `zz_groups_lang` ON `zz_groups`.`id`=`zz_groups_lang`.`id_record` AND `zz_groups_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).' WHERE `zz_groups`.`id` = `zz_users`.`idgruppo`) AS gruppo FROM `zz_users` WHERE `id` = :user_id AND `enabled` = 1 LIMIT 1', [
                ':user_id' => $user_id,
            ]);

            if (!empty($results)) {
                $this->user = User::with('group')->find($user_id);

                if (!API::isAPIRequest() && !empty($this->user->reset_token)) {
                    $this->user->reset_token = null;
                    $this->user->save();
                }
            }
        } catch (PDOException) {
            $this->destory();
        }
    }

    /**
     * Controlla la corrispondenza delle password ed eventualmente effettua un rehashing.
     *
     * @param string $password
     * @param string $hash
     * @param int    $user_id
     */
    protected function password_check($password, $hash, $user_id)
    {
        $result = false;
        $rehash = false;

        // Retrocompatibilità
        if ($hash == md5($password)) {
            $rehash = true;

            $result = true;
        }

        // Nuova versione
        if (password_verify($password, $hash)) {
            $rehash = password_needs_rehash($hash, self::$password_options['algorithm'], self::$password_options['options']);

            $result = true;
        }

        // Controllo in automatico per futuri cambiamenti dell'algoritmo di password
        if ($rehash) {
            $database = database();
            $database->update('zz_users', ['password' => self::hashPassword($password)], ['id' => $user_id]);
        }

        return $result;
    }

    /**
     * Memorizza le informazioni riguardanti l'utente all'interno della sessione.
     */
    protected function saveToSession()
    {
        if (session_status() == PHP_SESSION_ACTIVE && !empty($this->user)) {
            // Retrocompatibilità
            foreach ($this->user as $key => $value) {
                $_SESSION[$key] = $value;
            }
            $_SESSION['id_utente'] = $this->user->id;

            // Salva il token di autenticazione nella sessione (aggiorna sempre se presente)
            if (!empty($this->user->session_token)) {
                $_SESSION['auth_token'] = $this->user->session_token;
            }

            $identifier = md5($_SESSION['id_utente'].$_SERVER['HTTP_USER_AGENT']);
            if ((empty($_SESSION['last_active']) || time() < $_SESSION['last_active'] + (60 * 60)) && (empty($_SESSION['identifier']) || $_SESSION['identifier'] == $identifier)) {
                $_SESSION['last_active'] = time();
                $_SESSION['identifier'] = $identifier;
            }
        }
    }

    /**
     * Identifica l'utente tramite token senza utente associato.
     * Crea una sessione virtuale basata sui permessi del token.
     *
     * @param array $token_record Record del token dal database
     */
    protected function identifyByToken($token_record)
    {
        // Crea un utente virtuale per la sessione
        $this->user = (object) [
            'id' => 0,
            'username' => 'token_'.$token_record['id'],
            'nome' => 'Token Access',
            'cognome' => '',
            'email' => '',
            'gruppo' => 'Token',
            'is_admin' => false,
            'enabled' => true,
            'idanagrafica' => 0,
            'idgruppo' => 0,
        ];

        // Salva le informazioni del token nella classe
        $this->token_user = [
            'token_id' => $token_record['id'],
            'tipo_accesso' => $token_record['tipo_accesso'],
            'id_module_target' => $token_record['id_module_target'],
            'id_record_target' => $token_record['id_record_target'],
            'permessi' => $token_record['permessi'],
        ];

        // Salva nella sessione le informazioni del token (per retrocompatibilità)
        $_SESSION['token_user'] = true;
        $_SESSION['token_access'] = $this->token_user;
    }

    /**
     * Verifica se il token corrente è ancora valido.
     *
     * @return bool
     */
    protected function isTokenStillValid()
    {
        if (empty($this->token_user)) {
            return false;
        }

        $database = database();

        // Recupera il token dal database per verificare lo stato attuale
        $token_record = $database->fetchOne('SELECT * FROM `zz_otp_tokens` WHERE `id` = '.prepare($this->token_user['token_id']).' AND `enabled` = 1');

        if (empty($token_record)) {
            // Token non trovato o disabilitato
            $this->clearTokenAuthentication();

            return false;
        }

        // Verifica validità temporale
        if ($this->checkTokenValidity($token_record)) {
            // Token scaduto
            $this->clearTokenAuthentication();

            return false;
        }

        return true;
    }

    /**
     * Carica le informazioni del token dalla sessione nella classe.
     */
    protected function loadTokenFromSession()
    {
        if (!empty($_SESSION['token_access'])) {
            $this->token_user = $_SESSION['token_access'];
        }
    }

    /**
     * Pulisce l'autenticazione tramite token.
     */
    protected function clearTokenAuthentication()
    {
        $this->token_user = null;
        unset($_SESSION['token_user']);
        unset($_SESSION['token_access']);
    }

    /**
     * Genera un token di sessione sicuro e lo salva nel database.
     * Invalida automaticamente le sessioni precedenti.
     *
     * @param int $user_id ID dell'utente
     */
    protected function generateSessionToken($user_id)
    {
        // Genera un token sicuro di 64 caratteri esadecimali
        $token = bin2hex(random_bytes(32));

        $database = database();

        // Salva il token nel database (invalida automaticamente le sessioni precedenti)
        $database->update('zz_users', [
            'session_token' => $token,
        ], [
            'id' => $user_id,
        ]);

        // Salva il token nella sessione
        $_SESSION['auth_token'] = $token;

        // Aggiorna anche l'oggetto user se già caricato
        if (!empty($this->user) && $this->user->id == $user_id) {
            $this->user->session_token = $token;
        }
    }

    /**
     * Ricarica l'utente dal database dopo una riconnessione.
     * Questo metodo viene utilizzato per ripristinare lo stato dell'utente
     * quando la connessione al database viene persa e ripristinata.
     *
     * @return bool True se l'utente è stato ricaricato con successo, false altrimenti
     */
    protected function refreshUser()
    {
        // Verifica se c'è un ID utente nella sessione
        if (empty($_SESSION['id_utente'])) {
            return false;
        }

        try {
            $database = database();
            
            // Verifica che il database sia connesso
            if (!$database->isConnected() || !$database->isInstalled()) {
                return false;
            }

            // Ricarica l'utente dal database
            $this->identifyUser($_SESSION['id_utente']);

            // Se l'utente è stato caricato con successo, salva le informazioni nella sessione
            if (!empty($this->user)) {
                $this->saveToSession();
                return true;
            }

            return false;
        } catch (Exception $e) {
            // In caso di errore, logga il problema e restituisci false
            error_log('Errore durante il refresh dell\'utente: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Verifica che il token di sessione corrisponda a quello nel database.
     * Permette il login per utenti senza token (periodo di transizione).
     * Gestisce anche il ripristino del token quando la connessione al database viene persa.
     *
     * @return bool True se il token è valido o non presente, false altrimenti
     */
    protected function checkSessionToken()
    {
        // Se l'utente non è caricato, prova a ricaricarlo dalla sessione
        if (empty($this->user)) {
            // Prova a ricaricare l'utente dalla sessione (gestisce riconnessione al DB)
            if (!$this->refreshUser()) {
                return false;
            }
        }

        // Periodo di transizione: se l'utente non ha ancora un token nel DB, permetti l'accesso
        if (empty($this->user->session_token)) {
            return true;
        }

        // Verifica se il token è scaduto controllando le operazioni recenti
        $session_timeout = 100; 
        $database = database();
        
        $recent_operations = $database->fetchArray('SELECT COUNT(*) as count FROM zz_operations
            WHERE id_utente = :user_id
            AND DATE_ADD(created_at, INTERVAL :timeout MINUTE) >= NOW()', [
            ':user_id' => $this->user->id,
            ':timeout' => $session_timeout,
        ]);
        
        // Se non ci sono operazioni recenti, il token è scaduto -> resetta il token
        if (empty($recent_operations) || $recent_operations[0]['count'] == 0) {
            $database->update('zz_users', [
                'session_token' => null,
            ], [
                'id' => $this->user->id,
            ]);
            
            // Pulisci l'oggetto utente per forzare il logout
            $this->user = null;
            
            return false;
        }

        // Se c'è un token nel DB ma non in sessione, la sessione PHP è scaduta o invalida
        if (empty($_SESSION['auth_token'])) {
            return false;
        }

        // Confronta i token in modo sicuro contro timing attacks
        return hash_equals($this->user->session_token, $_SESSION['auth_token']);
    }

    /**
     * Verifica la validità temporale di un token.
     *
     * @param array $token_record Record del token dal database
     *
     * @return bool True se il token non è attivo, false se è attivo
     */
    private function checkTokenValidity($token_record)
    {
        $is_not_active = false;

        if (!empty($token_record['valido_dal']) && !empty($token_record['valido_al'])) {
            $is_not_active = strtotime((string) $token_record['valido_dal']) > time() || strtotime((string) $token_record['valido_al']) < time();
        } elseif (!empty($token_record['valido_dal']) && empty($token_record['valido_al'])) {
            $is_not_active = strtotime((string) $token_record['valido_dal']) > time();
        } elseif (empty($token_record['valido_dal']) && !empty($token_record['valido_al'])) {
            $is_not_active = strtotime((string) $token_record['valido_al']) < time();
        }

        return $is_not_active;
    }

    /**
     * Valida che l'URL sia interno al sistema e sicuro.
     *
     * @param string $url URL da validare
     *
     * @return bool
     */
    private function isValidInternalUrl($url)
    {
        if (empty($url)) {
            return false;
        }

        // Verifica che non contenga protocolli pericolosi
        if (str_contains($url, 'javascript:') || str_contains($url, 'data:')) {
            return false;
        }

        // Verifica che non sia un URL esterno (con protocollo http/https)
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return false;
        }

        $base_path = base_path_osm();

        // L'URL deve iniziare con il base_path del sistema o essere relativo
        if (str_starts_with($url, $base_path) || str_starts_with($url, '/')) {
            // Verifica che non sia un URL di actions.php senza parametri necessari
            $parsed_url = parse_url($url);
            $path = $parsed_url['path'] ?? '';

            // Esclude actions.php se chiamato direttamente senza id_module
            if (str_contains($path, 'actions.php')) {
                $query = $parsed_url['query'] ?? '';
                parse_str($query, $params);

                // actions.php richiede id_module per funzionare correttamente
                if (empty($params['id_module'])) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
