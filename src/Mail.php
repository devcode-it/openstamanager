<?php

/**
 * Classe per gestire le email (framework open source PHPMailer) in base alle impostazioni.
 *
 * @since 2.3
 */
class Mail extends PHPMailer
{
    protected $infos = [];

    public function __construct($exceptions = null)
    {
        parent::__construct($exceptions);

        // Configurazione di base
        $config = [
            'host' => Settings::get('Server SMTP'),
            'username' => Settings::get('Username SMTP'),
            'password' => Settings::get('Password SMTP'),
            'port' => Settings::get('Porta SMTP'),
            'secure' => Settings::get('Sicurezza SMTP'),
        ];

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
            if (in_array(strtolower($config['secure']), ['ssl', 'tls'])) {
                $this->SMTPSecure = strtolower($config['secure']);
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
