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
        if ($update == Update::getVersion()) {
            $update = null;
        }

        $module = Modules::get('Aggiornamenti');
        $link = base_path().'/controller.php?id_module='.$module->id;

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
