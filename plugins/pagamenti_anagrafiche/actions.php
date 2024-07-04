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

include_once __DIR__.'/../../core.php';

$operazione = filter('op');

switch ($operazione) {
    case 'addpagamento':
        if (count($dbo->selectOne('an_pagamenti_anagrafiche', 'id', ['idanagrafica' => $id_parent, 'mese' => post('mese')])) == 0) {
            $dbo->insert('an_pagamenti_anagrafiche', [
                'idanagrafica' => $id_parent,
                'mese' => post('mese'),
                'giorno_fisso' => post('giorno_fisso'),
            ]);
            $id_record = $dbo->lastInsertedID();

            flash()->info(tr('Aggiunta una nuova regola pagamento!'));
        } else {
            flash()->warning(tr('Esiste già una regola con lo stesso mese!'));
        }

        break;

    case 'updatepagamento':
        $opt_out_newsletter = post('disable_newsletter');

        $dbo->update('an_pagamenti_anagrafiche', [
            'mese' => post('mese'),
            'giorno_fisso' => post('giorno_fisso'),
        ], ['id' => $id_record]);

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'deletepagamento':
        $id_record = post('id_record');
        $dbo->query('DELETE FROM `an_pagamenti_anagrafiche` WHERE `id` = '.prepare($id_record).'');

        flash()->info(tr('Regola pagamento eliminata!'));

        break;
}
