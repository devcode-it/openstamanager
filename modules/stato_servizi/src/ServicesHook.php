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
        if (Services::isEnabled()) {
            $limite_scadenze = (new Carbon())->addDays(60);
            $message = '';

            // Elaborazione dei servizi in scadenza
            $servizi_in_scadenza = Services::getServiziInScadenza($limite_scadenze);
            if (!$servizi_in_scadenza->isEmpty()) {
                $message .= '<i class="fa fa-clock-o text-warning"> </i> ';
                $message .= tr('I seguenti servizi sono in scadenza:<ul><li> _LIST_', [
                    '_LIST_' => implode('</li><li>', $servizi_in_scadenza->pluck('nome')->all()),
                ]).'</ul>';
            }

            // Elaborazione delle risorse Services scadute
            $risorse_scadute = Services::getRisorseScadute();
            if (!$risorse_scadute->isEmpty()) {
                $message .= '<i class="fa fa-exclamation-triangle text-danger"> </i> ';
                $message .= tr('Le seguenti risorse sono scadute:<ul><li> _LIST_', [
                    '_LIST_' => implode('</li><li>', $risorse_scadute->pluck('name')->all()),
                ]).'</ul>';
            }

            // Elaborazione dei servizi scaduti
            $servizi_scaduti = Services::getServiziScaduti();
            if (!$servizi_scaduti->isEmpty()) {
                $message .= '<i class="fa fa-exclamation-triangle text-danger"> </i> ';
                $message .= tr('I seguenti servizi sono scaduti:<ul><li> _LIST_', [
                    '_LIST_' => implode('</li><li>', $servizi_scaduti->pluck('nome')->all()),
                ]).'</ul>';
            }

            // Elaborazione delle risorse Services in scadenza
            $risorse_in_scadenza = Services::getRisorseInScadenza($limite_scadenze);
            if (!$risorse_in_scadenza->isEmpty()) {
                $message .= '<i class="fa fa-clock-o text-warning"> </i> ';
                $message .= tr('Le seguenti risorse sono in scadenza:<ul><li> _LIST_', [
                    '_LIST_' => implode('</li><li>', $risorse_in_scadenza->pluck('name')->all()),
                ]).'</ul>';
            }

            $id_module = (new Module())->getByName('Stato dei servizi')->id_record;
        }

        return [
            'icon' => null,
            'message' => $message,
            'link' => base_path().'/controller.php?id_module='.$id_module,
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
