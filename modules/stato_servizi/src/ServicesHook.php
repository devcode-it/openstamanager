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
        $limite_scadenze = (new Carbon())->addDays(60);
        $message = '';

        // Elaborazione dei servizi in scadenza
        $servizi_in_scadenza = Services::getServiziInScadenza($limite_scadenze);
        if (!$servizi_in_scadenza->isEmpty()) {
            $message .= tr('I seguenti servizi sono in scadenza: _LIST_', [
                '_LIST_' => implode(', ', $servizi_in_scadenza->pluck('nome')->all()),
            ]).'. ';
        }

        // Elaborazione delle risorse Services in scadenza
        $risorse_in_scadenza = Services::getRisorseInScadenza($limite_scadenze);
        if (!$risorse_in_scadenza->isEmpty()) {
            $message .= tr('Le seguenti risorse Services sono in scadenza: _LIST_', [
                '_LIST_' => implode(', ', $risorse_in_scadenza->pluck('name')->all()),
            ]);
        }

        $module = Module::pool('Stato dei servizi');

        return [
            'icon' => 'fa fa-refresh text-warning',
            'message' => $message,
            'link' => base_path().'/controller.php?id_module='.$module->id,
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
