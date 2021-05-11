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
use Modules\Fatture\Fattura;
use Plugins\ExportFE\Interaction;
use Tasks\Manager;

/**
 * Task dedicata all'importazione forzata delle ricevute per Fatture in stato di Attesa da più di 7 giorni.
 * Questa funzione è necessaria per evitare eventuali problemi causati da importazioni segnato come eseguite ma non completate con successo, che si verificano in rari casi durante l'interazione con il sistema di gestione Fatture Elettroniche.
 *
 * @see ReceiptTask Gestione ricevute rilevate correttamente.
 */
class MissingReceiptTask extends Manager
{
    public function execute()
    {
        if (!Interaction::isEnabled()) {
            return;
        }

        // Controllo se ci sono fatture in elaborazione da più di 7 giorni per le quali non ho ancora una ricevuta
        $data_limite = (new Carbon())->subDays(7);
        $in_attesa = Fattura::vendita()
            ->where('codice_stato_fe', 'WAIT')
            ->where('data_stato_fe', '>=', $_SESSION['period_start'])
            ->where('data_stato_fe', '<', $data_limite)
            ->orderBy('data_stato_fe')
            ->get();

        // Ricerca delle ricevute dedicate
        foreach ($in_attesa as $fattura){
            $ricevute = Interaction::getInvoiceRecepits($fattura->id);

            // Importazione di tutte le ricevute trovate
            foreach ($ricevute as $ricevuta){
                $name = $ricevuta['name'];

                Ricevuta::process($name);
            }
        }
    }
}
