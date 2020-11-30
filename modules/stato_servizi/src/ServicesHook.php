<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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
        $response = Services::request('POST', 'informazioni_servizi');
        $body = Services::responseBody($response);

        return $body['services'];
    }

    public function response()
    {
        $servizi = $this->getCache()->content;

        // Elaborazione dei servizi in scadenza
        $limite_scadenze = (new Carbon())->addDays(60);
        $servizi_in_scadenza = [];
        foreach ($servizi as $servizio) {
            // Gestione per data di scadenza
            $scadenza = new Carbon($servizio['expiration_at']);
            if (
                (isset($servizio['expiration_at']) && $scadenza->lessThan($limite_scadenze))
            ) {
                $servizi_in_scadenza[] = $servizio['name'].' ('.$scadenza->diffForHumans().')';
            }

            // Gestione per crediti
            elseif (
                (isset($servizio['credits']) && $servizio['credits'] < 100)
            ) {
                $servizi_in_scadenza[] = $servizio['name'].' ('.$servizio['credits'].' crediti)';
            }
        }

        $message = tr('I seguenti servizi sono in scadenza: _LIST_', [
            '_LIST_' => implode(', ', $servizi_in_scadenza),
        ]);

        return [
            'icon' => 'fa fa-refresh text-warning',
            'message' => $message,
            'show' => !empty($servizi_in_scadenza),
        ];
    }
}
