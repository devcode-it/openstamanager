<?php

/**
 * Classe per gestire le email (framework open source PHPMailer) in base alle impostazioni.
 *
 * @since 2.3
 */
class Mail extends PHPMailer\PHPMailer\PHPMailer
{
    /** @var array Elenco degli account email disponibili */
    protected static $accounts = [];

    protected $infos = [];

    /**
     * Restituisce tutte le informazioni di tutti i plugin installati.
     *
     * @return array
     */
    public static function getAccounts()
    {
        if (empty(self::$accounts)) {
            $database = Database::getConnection();

            $results = $database->fetchArray('SELECT * FROM zz_smtp WHERE deleted = 0');

            $accounts = [];

            foreach ($results as $result) {
                $accounts[$result['id']] = $result;
                $accounts[$result['name']] = $result['id'];

                if (!empty($result['main'])) {
                    $accounts['default'] = $result['id'];
                }
            }

            self::$accounts = $accounts;
        }

        return self::$accounts;
    }

    /**
     * Restituisce le informazioni relative a un singolo modulo specificato.
     *
     * @param string|int $plugin
     *
     * @return array
     */
    public static function get($account = null)
    {
        if (!is_numeric($account) && !empty(self::getAccounts()[$account])) {
            $account = self::getAccounts()[$account];
        }

        if (empty($account)) {
            $account = self::getAccounts()['default'];
        }

        return self::getAccounts()[$account];
    }

    public function __construct($account = null, $exceptions = null)
    {
        parent::__construct($exceptions);

        // Configurazione di base
        $config = self::get($account);

        // Preparazione email
        $this->IsHTML(true);

        if (!empty($config['host'])) {
            $this->IsSMTP(true);

            // Impostazioni di debug
            $this->SMTPDebug = 3;
            $this->Debugoutput = function ($str, $level) {
                $this->infos[] = $str;
            };

            // Impostazioni dell'host
            $this->Host = $config['host'];
            $this->Port = $config['port'];

            // Impostazioni di autenticazione
            if (!empty($config['username'])) {
                $this->SMTPAuth = true;
                $this->Username = $config['username'];
                $this->Password = $config['password'];
            }

            // Impostazioni di sicurezza
            if (in_array(strtolower($config['encryption']), ['ssl', 'tls'])) {
                $this->SMTPSecure = strtolower($config['encryption']);
            }
        }

        $this->WordWrap = 78;
    }

    public function send()
    {
        global $logger;

        if (empty($this->AltBody)) {
            $this->AltBody = strip_tags($this->Body);
        }

        $result = parent::send();

        $this->SmtpClose();

        // Segnalazione degli errori
        foreach ($this->infos as $info) {
            $logger->addRecord(\Monolog\Logger::ERROR, $info);
        }

        return $result;
    }
}
