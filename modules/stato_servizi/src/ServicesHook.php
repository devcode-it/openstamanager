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

class ServicesHook extends Manager
{
    public function response()
    {
        // Elaborazione dei servizi in scadenza
        $limite_scadenze = (new Carbon())->addDays(60);
        $risorse_in_scadenza = Services::getRisorseInScadenza($limite_scadenze);

        $message = tr('I seguenti servizi sono in scadenza: _LIST_', [
            '_LIST_' => implode(', ', $risorse_in_scadenza->pluck('nome')->all()),
        ]);

        return [
            'icon' => 'fa fa-refresh text-warning',
            'message' => $message,
            'show' => !$risorse_in_scadenza->isEmpty(),
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
