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

use Tasks\Manager;
use API\Services;
use Tasks\Manager;

class ServicesHookTask extends Manager
{
    public $cache_name = 'Informazioni su Services';

    public function execute()
    {
        $result = [
            'response' => 2,
            'message' => tr('Token Services non configurato. Aggiornamento saltato.'),
        ];

        if (!Services::isEnabled()) {
            return $result;
        }

        try {
            $informazioni = Services::getInformazioni(true);

            if (!empty($informazioni)) {
                $result = [
                    'response' => 1,
                    'message' => tr('Controllo servizi attivi completato!'),
                ];
            } else {
                $result = [
                    'response' => 2,
                    'message' => tr('Risposta vuota dal servizio Services. Cache mantenuta.'),
                ];
            }
        } catch (\Exception $e) {
            $result = [
                'response' => 2,
                'message' => tr('Errore nel controllo dei servizi attivi! _error_. Cache mantenuta.', [
                    '_error_' => $e->getMessage(),
                ]),
            ];
        }

        return $result;
    }
}
