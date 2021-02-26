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

namespace Plugins\ReceiptFE;

use Carbon\Carbon;
use Hooks\Manager;
use Modules;
use Modules\Fatture\Fattura;
use Plugins;

class NotificheRicevuteHook extends Manager
{
    public function response()
    {
        // Messaggio informativo su fatture con stato di errore
        $con_errore = Fattura::vendita()
            ->whereIn('codice_stato_fe', ['NS', 'ERR', 'EC02'])
            ->where('data_stato_fe', '>=', $_SESSION['period_start'])
            ->orderBy('data_stato_fe')
            ->count();

        // Controllo se ci sono fatture in elaborazione da più di 7 giorni per le quali non ho ancora una ricevuta
        $data_limite = (new Carbon())->subDays(7);
        $in_attesa = Fattura::vendita()
            ->where('codice_stato_fe', 'WAIT')
            ->where('data_stato_fe', '>=', $_SESSION['period_start'])
            ->where('data_stato_fe', '<', $data_limite)
            ->orderBy('data_stato_fe')
            ->count();

        // Messaggio di importazione
        if (!empty($in_attesa) && !empty($con_errore)) {
            $message = tr('Sono presenti _ERR_ fatture elettroniche con ricevute di scarto o errori di trasmissione, _WAIT_ in attesa di ricevuta da più di 7 giorni', [
                '_ERR_' => $con_errore,
                '_WAIT_' => $in_attesa,
            ]);
        } elseif (empty($in_attesa) && !empty($con_errore)) {
            $message = tr('Sono presenti _ERR_ fatture elettroniche con ricevute di scarto o errori di trasmissione', [
                '_ERR_' => $con_errore,
            ]);
        }
        if (!empty($in_attesa) && empty($con_errore)) {
            $message = tr('Sono presenti _WAIT_ in attesa di ricevuta da più di 7 giorni', [
                '_WAIT_' => $in_attesa,
            ]);
        }

        $module = Modules::get('Fatture di vendita');
        $plugin = Plugins::get('Ricevute FE');

        return [
            'icon' => 'fa fa-ticket text-yellow',
            'message' => $message,
            'show' => $con_errore != 0 || $in_attesa != 0,
            'link' => base_path().'/controller.php?id_module='.$module->id.'#tab_'.$plugin->id,
        ];
    }

    public function execute()
    {
    }

    public function needsExecution()
    {
        return false;
    }
}
