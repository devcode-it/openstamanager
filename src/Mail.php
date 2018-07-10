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
     * Restituisce tutte le informazioni di tutti gli account email presenti.
     *
     * @return array
     */
    public static function getAccounts()
    {
        if (empty(self::$accounts)) {
            $database = Database::getConnection();

            $results = $database->fetchArray('SELECT * FROM zz_smtps WHERE deleted = 0');

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
     * Restituisce le informazioni relative a un singolo account email specificato.
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
     * Restituisce tutte le informazioni di tutti i template presenti.
     *
     * @return array
     */
    public static function getTemplates()
    {
        if (empty(self::$templates)) {
            $database = Database::getConnection();

            $results = $database->fetchArray('SELECT * FROM zz_emails WHERE deleted = 0');

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
     * Restituisce le variabili relative a un singolo template specificato.
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

    /**
     * Aggiunge gli allegati all'email.
     *
     * @param array $prints
     * @param array $files
     */
    public function attach($prints, $files)
    {
        $id_module = App::getCurrentModule()['id'];
        $id_record = App::getCurrentElement();

        // Elenco degli allegati
        $attachments = [];

        // Stampe
        foreach ($prints as $print) {
            $print = Prints::get($print);

            // Utilizzo di una cartella particolare per il salvataggio temporaneo degli allegati
            $filename = DOCROOT.'/files/attachments/'.$print['title'].' - '.$id_record.'.pdf';

            Prints::render($print['id'], $id_record, $filename);

            $attachments[] = [
                'path' => $filename,
                'name' => $print['title'].'.pdf',
            ];
        }

        // Allegati del record
        $selected = [];
        if (!empty($files)) {
            $selected = $dbo->fetchArray('SELECT * FROM zz_files WHERE id IN ('.implode(',', $files).') AND id_module = '.prepare($id_module).' AND id_record = '.prepare($id_record));
        }

        foreach ($selected as $attachment) {
            $attachments[] = [
                'path' => $upload_dir.'/'.$attachment['filename'],
                'name' => $attachment['nome'],
            ];
        }

        // Allegati dell'Azienda predefinita
        $anagrafiche = Modules::get('Anagrafiche');

        $selected = [];
        if (!empty($files)) {
            $selected = $dbo->fetchArray('SELECT * FROM zz_files WHERE id IN ('.implode(',', $files).') AND id_module != '.prepare($id_module));
        }

        foreach ($selected as $attachment) {
            $attachments[] = [
                'path' => DOCROOT.'/files/'.$anagrafiche['directory'].'/'.$attachment['filename'],
                'name' => $attachment['nome'],
            ];
        }

        // Aggiunta allegati
        foreach ($attachments as $attachment) {
            $this->AddAttachment($attachment['path'], $attachment['name']);
        }
    }

    /**
     * Aggiunge i detinatari.
     *
     * @param array $receivers
     * @param array $types
     */
    public function addReceivers($receivers, $types)
    {
        // Destinatari
        foreach ($receivers as $key => $destinatario) {
            $type = $types[$key];

            $pieces = explode('<', $destinatario);
            $count = count($pieces);

            $name = null;
            if ($count > 1) {
                $email = substr(end($pieces), 0, -1);
                $name = substr($destinatario, 0, strpos($destinatario, '<'.$email));
            } else {
                $email = $destinatario;
            }

            if (!empty($email)) {
                if ($type == 'a') {
                    $this->AddAddress($email, $name);
                } elseif ($type == 'cc') {
                    $this->AddCC($email, $name);
                } elseif ($type == 'bcc') {
                    $this->AddBCC($email, $name);
                }
            }
        }
    }

    /**
     * Effettua un test di connessione all'email SMTP.
     *
     * @return bool
     */
    public function testSMTP()
    {
        if ($this->IsSMTP() && $this->smtpConnect()) {
            $this->smtpClose();

            return true;
        }

        return false;
    }
}
