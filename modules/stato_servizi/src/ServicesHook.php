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

namespace Modules\StatoServizi;

use API\Services;
use Hooks\Manager;
use Models\Module;
use Models\Cache;
use Carbon\Carbon;
class ServicesHook extends Manager
{
    public function response()
    {
        $message = null;

        if (Services::isEnabled()) {
            $limite_scadenze = (new Carbon())->addDays(60);
            $message = "";

            $cache = Cache::where('name', 'Informazioni su Services')->first();
            $services = $cache->content;

            //Filtra i risultati che hanno expiration_at fra oggi e $limite_scadenze
            $servizi_in_scadenza = array_filter($services, function ($service) use ($limite_scadenze) {
                return is_array($service) && isset($service['expiration_at']) && Carbon::parse($service['expiration_at'])->between(Carbon::now(), $limite_scadenze);
            });

            if( !empty($servizi_in_scadenza) ){
                $message .= '<i class="fa fa-clock-o text-warning"> </i> ';
                $message .= tr('I seguenti servizi sono in scadenza:<ul><li> _LIST_', [
                    '_LIST_' => implode('</li><li>', array_column($servizi_in_scadenza, 'name')),
                ]).'</ul>';
            }
        }

        return [
            'icon' => null,
            'message' => $message,
            'link' => base_path().'/controller.php?id_module='.Module::where('name','Stato dei servizi')->first()->id,
            'show' => Services::isEnabled() && !empty($message),
        ];
    }

    public function execute()
    {
        return false;
    }

    public function needsExecution()
    {
        return false;
    }
}
