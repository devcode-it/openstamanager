<?php

use Models\MailAccount;

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
            $this->SMTPDebug = App::debug() ? 2 : 0;
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

            if (!empty($config['ssl_no_verify'])) {
                $this->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                        ],
                ];
            }
        }

        $this->From = $config['from_address'];
        $this->FromName = $config['from_name'];

        $this->WordWrap = 78;
    }

    /**
     * Restituisce tutte le informazioni di tutti gli account email presenti.
     *
     * @return array
     */
    public static function getAccounts()
    {
        return MailAccount::getAll();
    }

    /**
     * Restituisce le informazioni relative a un singolo account email specificato.
     *
     * @param string|int $account
     *
     * @return array
     */
    public static function get($account = null)
    {
        $accounts = self::getAccounts();

        $result = MailAccount::get($account);

        if (empty($result)) {
            $result = $accounts->first(function ($item) {
                return !empty($item->predefined);
            });
        }

        return $result;
    }

    /**
     * Restituisce tutte le informazioni di tutti i template presenti.
     *
     * @return array
     */
    public static function getTemplates()
    {
        if (empty(self::$templates)) {
            $database = database();

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

    /**
     * Testa la connessione al server SMTP.
     *
     * @return bool
     */
    public function testSMTP()
    {
        if ($this->smtpConnect()) {
            $this->smtpClose();

            return true;
        }

        return false;
    }

    /**
     * Invia l'email impostata.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function send()
    {
        if (empty($this->AltBody)) {
            $this->AltBody = strip_tags($this->Body);
        }

        $exception = null;
        try {
            $result = parent::send();
        } catch (PHPMailer\PHPMailer\Exception $e) {
            $result = false;
            $exception = $e;
        }

        $this->SmtpClose();

        // Segnalazione degli errori
        if (!$result) {
            $logger = logger();
            foreach ($this->infos as $info) {
                $logger->addRecord(\Monolog\Logger::ERROR, $info);
            }
        }

        if (!empty($exception)) {
            throw $exception;
        }

        return $result;
    }

    public function setTemplate(array $template)
    {
        // Reply To
        if (!empty($template['reply_to'])) {
            $this->AddReplyTo($template['reply_to']);
        }

        // CC
        if (!empty($template['cc'])) {
            $this->AddCC($template['cc']);
        }

        // BCC
        if (!empty($template['bcc'])) {
            $this->AddBCC($template['bcc']);
        }
    }

    /**
     * Aggiunge un destinatario.
     *
     * @param array $receiver
     * @param array $type
     */
    public function addReceiver($receiver, $type = null)
    {
        $pieces = explode('<', $receiver);
        $count = count($pieces);

        $name = null;
        if ($count > 1) {
            $email = substr(end($pieces), 0, -1);
            $name = substr($receiver, 0, strpos($receiver, '<'.$email));
        } else {
            $email = $receiver;
        }

        if (!empty($email)) {
            if ($type == 'cc') {
                $this->AddCC($email, $name);
            } elseif ($type == 'bcc') {
                $this->AddBCC($email, $name);
            } else {
                $this->AddAddress($email, $name);
            }
        }
    }
}
