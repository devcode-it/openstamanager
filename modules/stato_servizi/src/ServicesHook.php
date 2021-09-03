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
use Hooks\CachedManager;

class ServicesHook extends CachedManager
{
    public function getCacheName()
    {
        return 'Informazioni su Services';
    }

    public function cacheData()
    {
        $response = Services::request('GET', 'info');

        return Services::responseBody($response);
    }

    public function response()
    {
        $servizi = $this->getCache()->content;
        $limite_scadenze = (new Carbon())->addDays(60);

        // Elaborazione dei servizi in scadenza
        $risorse_in_scadenza = self::getRisorseInScadenza($servizi['risorse-api'], $limite_scadenze);

        $message = tr('I seguenti servizi sono in scadenza: _LIST_', [
            '_LIST_' => implode(', ', $risorse_in_scadenza->pluck('nome')->all()),
        ]);

        return [
            'icon' => 'fa fa-refresh text-warning',
            'message' => $message,
            'show' => !$risorse_in_scadenza->isEmpty(),
        ];
    }

    /**
     * Restituisce l'elenco delle risorse API in scadenza, causa data oppure crediti.
     *
     * @param $servizi
     */
    public static function getRisorseInScadenza($risorse, $limite_scadenze)
    {
        // Elaborazione dei servizi in scadenza
        $risorse_in_scadenza = collect($risorse)
            ->filter(function ($item) use ($limite_scadenze) {
                return (isset($item['expiration_at']) && Carbon::parse($item['expiration_at'])->lessThan($limite_scadenze))
                    || (isset($item['credits']) && $item['credits'] < 100);
            });

        return $risorse_in_scadenza->transform(function ($item, $key) {
            return [
                'nome' => $item['name'],
                'data_scadenza' => $item['expiration_at'],
                'crediti' => $item['credits'],
            ];
        });
    }
}
