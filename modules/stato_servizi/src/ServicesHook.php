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
use Carbon\Carbon;
use Hooks\Manager;
use Models\Module;

class ServicesHook extends Manager
{
    public function response()
    {
        $message = null;

        if (Services::isEnabled()) {
            $limite_scadenze = (new Carbon())->addDays(60);
            $message = '';

            // Recupera le risorse attive una sola volta dalla cache
            $risorse = Services::getRisorseAttive();

            // Risorse scadute (expiration_at nel passato o credits < 0)
            $risorse_scadute = $risorse->filter(fn ($item) => is_array($item) && ((isset($item['expiration_at']) && Carbon::parse($item['expiration_at'])->lessThan(Carbon::now())) || (isset($item['credits']) && $item['credits'] < 0)));

            if ($risorse_scadute->isNotEmpty()) {
                $message .= tr('I seguenti servizi sono scaduti:<ul><li> _LIST_', [
                    '_LIST_' => implode('</li><li>', $risorse_scadute->pluck('name')->toArray()),
                ]).'</ul>';
            }

            // Risorse in scadenza nei prossimi 60 giorni (o credits < 100)
            $risorse_in_scadenza = $risorse->filter(fn ($item) => is_array($item) && ((isset($item['expiration_at']) && Carbon::parse($item['expiration_at'])->greaterThan(Carbon::now()) && Carbon::parse($item['expiration_at'])->lessThan($limite_scadenze)) || (isset($item['credits']) && $item['credits'] >= 0 && $item['credits'] < 100)));

            if ($risorse_in_scadenza->isNotEmpty()) {
                $message .= tr('I seguenti servizi sono in scadenza:<ul><li> _LIST_', [
                    '_LIST_' => implode('</li><li>', $risorse_in_scadenza->pluck('name')->toArray()),
                ]).'</ul>';
            }
        }

        return [
            'icon' => 'fa fa-clock-o text-warning',
            'message' => $message,
            'link' => base_path_osm().'/controller.php?id_module='.Module::where('name', 'Stato dei servizi')->first()->id,
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
