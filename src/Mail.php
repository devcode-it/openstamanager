<?php

/**
 * Classe per gestire le email in base alle impostazioni, basata sul framework open-source PHPMailer.
 *
 * @since 2.3
 */
class Mail extends PHPMailer\PHPMailer\PHPMailer
{
    /** @var array Elenco degli account email disponibili */
    protected static $accounts = [];

    /** @var array Elenco dei template email disponibili */
    protected static $templates = [];
    protected static $references = [];
    /** @var array Elenco dei template email per modulo */
    protected static $modules = [];

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

            $results = $database->fetchArray('SELECT * FROM zz_smtp WHERE deleted_at IS NULL');

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
     * @param string|int $template
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

    /**
     * Restituisce tutte le informazioni di tutti i plugin installati.
     *
     * @return array
     */
    public static function getTemplates()
    {
        if (empty(self::$templates)) {
            $database = Database::getConnection();

            $results = $database->fetchArray('SELECT * FROM zz_emails WHERE deleted_at IS NULL');

            $templates = [];
            $references = [];

            // Inizializzazione dei riferimenti
            foreach (Modules::getModules() as $module) {
                self::$modules[$module['id']] = [];
            }

            foreach ($results as $result) {
                $templates[$result['id']] = $result;
                $references[$result['name']] = $result['id'];

                self::$modules[$result['id_module']][] = $result['id'];
            }

            self::$templates = $templates;
            self::$references = $references;
        }

        return self::$templates;
    }

    /**
     * Restituisce le informazioni relative a un singolo template specificato.
     *
     * @param string|int $template
     *
     * @return array
     */
    public static function getTemplate($template)
    {
        $templates = self::getTemplates();

        if (!is_numeric($template) && !empty(self::$references[$template])) {
            $template = self::$references[$template];
        }

        return $templates[$template];
    }

    /**
     * Restituisce le informazioni relative a un singolo template specificato.
     *
     * @param string|int $template
     *
     * @return array
     */
    public static function getTemplateVariables($template, $id_record)
    {
        $template = self::getTemplate($template);

        $database = Database::getConnection();
        $dbo = $database;

        // Lettura delle variabili nei singoli moduli
        $variables = include Modules::filepath($template['id_module'], 'variables.php');

        return (array) $variables;
    }

    /**
     * Restituisce le informazioni relative ai template di un singolo modulo specificato.
     *
     * @param string|int $module
     *
     * @return array
     */
    public static function getModuleTemplates($module)
    {
        $module_id = Modules::get($module)['id'];

        self::getTemplates();

        $result = [];

        foreach ((array) self::$modules[$module_id] as $value) {
            $result[] = self::getTemplate($value);
        }

        return $result;
    }

    public function __construct($account = null, $exceptions = null)
    {
        parent::__construct($exceptions);

        $this->CharSet = 'UTF-8';

        // Configurazione di base
        $config = self::get($account);

        // Preparazione email
        $this->IsHTML(true);

        if (!empty($config['server'])) {
            $this->IsSMTP(true);

            // Impostazioni di debug
            $this->SMTPDebug = 4;
            $this->Debugoutput = function ($str, $level) {
                $this->infos[] = $str;
            };

            // Impostazioni dell'host
            $this->Host = $config['server'];
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

        $this->From = $config['from_address'];
        $this->FromName = $config['from_name'];

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

    public function testSMTP()
    {
        if ($this->IsSMTP() && $this->smtpConnect()) {
            $this->smtpClose();

            return true;
        }

        return false;
    }
}
