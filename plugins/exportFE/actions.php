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

include_once __DIR__.'/init.php';

use Plugins\ExportFE\Interaction;
use Plugins\ReceiptFE\Ricevuta;

switch (filter('op')) {
    case 'generate':
        if (!empty($fattura_pa)) {
            $file = $fattura_pa->save();

            flash()->info(tr('Fattura elettronica generata correttamente!'));

            if (!$fattura_pa->isValid()) {
                $errors = $fattura_pa->getErrors();

                flash()->warning(tr('La fattura elettronica potrebbe avere delle irregolarità!').' '.tr('Controllare i seguenti campi: _LIST_', [
                    '_LIST_' => implode(', ', $errors),
                ]).'.');
            }
        } else {
            flash()->error(tr('Impossibile generare la fattura elettronica'));
        }

        break;

    case 'send':
        $result = Interaction::sendInvoice($id_record);

        echo json_encode($result);

        break;

    case 'verify':
        $result = Interaction::getInvoiceRecepits($id_record);
        $last_recepit = $result['results'][0];

        // Messaggi relativi
        if (empty($last_recepit)) {
            echo json_encode($result);

            return;
        }

        // Importazione ultima ricevuta individuata
        $fattura = Ricevuta::process($last_recepit);
        $numero_esterno = $fattura ? $fattura->numero_esterno : null;

        echo json_encode([
            'file' => $last_recepit,
            'fattura' => $numero_esterno,
        ]);

        break;

    case 'gestione_ricevuta':
        $name = filter('name');
        $type = filter('type');

        $cambia_stato = $type != 'download';
        $fattura = Ricevuta::process($name, $cambia_stato);

        $numero_esterno = $fattura ? $fattura->numero_esterno : null;

        echo json_encode([
            'file' => $name,
            'fattura' => $fattura,
        ]);

        break;
}
