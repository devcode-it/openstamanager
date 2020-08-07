<?php

namespace Modules\Aggiornamenti;

use GuzzleHttp\Client;
use Hooks\CachedManager;
use Modules;
use Update;

/**
 * Hook dedicato all'individuazione di nuove versioni del gestionale, pubblicate sulla repository ufficiale di GitHub.
 */
class UpdateHook extends CachedManager
{
    protected static $client = null;

    public function getCacheName()
    {
        return 'Ultima versione di OpenSTAManager disponibile';
    }

    public function cacheData()
    {
        return self::isAvailable();
    }

    public function response()
    {
        $update = $this->getCache()->content;

        $module = Modules::get('Aggiornamenti');
        $link = ROOTDIR.'/controller.php?id_module='.$module->id;

        $message = tr("E' disponibile la versione _VERSION_ del gestionale", [
            '_VERSION_' => $update,
        ]);

        return [
            'icon' => 'fa fa-download text-info',
            'link' => $link,
            'message' => $message,
            'show' => !empty($update),
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

        if (!$api['prerelease'] or setting('Abilita canale pre-release per aggiornamenti')) {
            $version[0] = ltrim($api['tag_name'], 'v');
            $version[1] = !empty($api['prerelease']) ? 'beta' : 'stabile';
            $current = Update::getVersion();

            if (version_compare($current, $version[0]) < 0) {
                return $version;
            }
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
