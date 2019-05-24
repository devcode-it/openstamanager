<?php

namespace Modules\Aggiornamenti;

use Common\HookManager;
use GuzzleHttp\Client;
use Modules;
use Update;

class UpdateHook extends HookManager
{
    protected static $client = null;

    public function manage()
    {
        $result = self::isAvailable();

        return $result;
    }

    public function response($update)
    {
        $module = Modules::get('Aggiornamenti');
        $link = ROOTDIR.'/controller.php?id_module='.$module->id;

        $message = tr("E' disponibile la versione _VERSION_ del gestionale", [
            '_VERSION_' => $update,
        ]);

        return [
            'icon' => 'fa fa-download text-info',
            'link' => $link,
            'message' => $message,
            'notify' => !empty($update),
        ];
    }

    /**
     * Controlla se è disponibile un aggiornamento nella repository GitHub.
     *
     * @return string|bool
     */
    public static function isAvailable()
    {
        $api = self::getAPI();

        $version = ltrim($api['tag_name'], 'v');
        $current = Update::getVersion();

        if (version_compare($current, $version) < 0) {
            return $version;
        }

        return false;
    }

    /**
     * Restituisce l'oggetto per la connessione all'API del progetto.
     *
     * @return Client
     */
    protected static function getClient()
    {
        if (!isset(self::$client)) {
            self::$client = new Client([
                'base_uri' => 'https://api.github.com/repos/devcode-it/openstamanager/',
                'verify' => false,
            ]);
        }

        return self::$client;
    }

    /**
     * Restituisce i contenuti JSON dell'API del progetto.
     *
     * @return array
     */
    protected static function getAPI()
    {
        $response = self::getClient()->request('GET', 'releases');
        $body = $response->getBody();

        return json_decode($body, true)[0];
    }
}
