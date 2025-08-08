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

use Tasks\Manager;
use Models\Module;
use Carbon\Carbon;
use GuzzleHttp\Client;

class UpdateHookTask extends Manager
{
    protected static $client;

    public function execute()
    {
        $result = [
            'response' => 1,
            'message' => tr('Ricerca nuovo aggiornamento completata! Nessun nuovo aggiornamento disponibile.'),
        ];      
        
        try {
            $api = self::getLastRelease();
            $update = null;
            if (!$api['prerelease'] or setting('Abilita canale pre-release per aggiornamenti')) {
                $version[0] = ltrim((string) $api['tag_name'], 'v');
                $version[1] = !empty($api['prerelease']) ? 'beta' : 'stabile';
                $current = \Update::getVersion();
    
                if (version_compare($current, $version[0]) < 0) {
                    $update = $version[0];
                }
            }
    
            database()->update('zz_cache', [
                'content' => json_encode($version),
                'expire_at' => Carbon::now(),
            ], [
                'name' => 'Ultima versione di OpenSTAManager disponibile',
            ]);
    
            if (empty($update) || empty(setting('Attiva aggiornamenti'))) {
                return $result;
            }else{
                $module = Module::where('name', 'Aggiornamenti')->first();
                $link = !empty($module) ? base_path().'/controller.php?id_module='.$module->id : '#';
        
                $result = [
                    'response' => 2,
                    'message' => tr("E' disponibile la versione _VERSION_ del gestionale", [
                        '_VERSION_' => $update,
                    ]),
                ];
            }
        } catch (\Exception $e) {
            $result = [
                'response' => 0,
                'message' => tr('Ricerca nuovo aggiornamento fallita! _error_',[
                    '_error_' => $e->getMessage(),
                ]),
            ];
        }        

        return $result;
    }

    protected static function getLastRelease()
    {
        if (!isset(self::$client)) {
            self::$client = new Client([
                'base_uri' => 'https://api.github.com/repos/devcode-it/openstamanager/',
                'verify' => false,
            ]);
        }

        $response = self::$client->request('GET', 'releases');
        $body = $response->getBody();

        $result = json_decode($body, true);
        if( $result[0] ){
            return $result[0];
        }else{
            return [];
        }
    }
}
