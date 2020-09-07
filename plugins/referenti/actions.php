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

include_once __DIR__.'/../../core.php';

$operazione = filter('op');

switch ($operazione) {
    case 'addreferente':
        if (!empty(post('nome'))) {
            $dbo->insert('an_referenti', [
                'idanagrafica' => $id_parent,
                'nome' => post('nome'),
                'mansione' => post('mansione'),
                'telefono' => post('telefono'),
                'email' => post('email'),
                'idsede' => post('idsede'),
            ]);
            $id_record = $dbo->lastInsertedID();

            if (isAjaxRequest() && !empty($id_record)) {
                echo json_encode(['id' => $id_record, 'text' => post('nome')]);
            }

            flash()->info(tr('Aggiunto nuovo referente!'));
        } else {
            flash()->warning(tr('Errore durante aggiunta del referente'));
        }

        break;

    case 'updatereferente':
        $dbo->update('an_referenti', [
            'idanagrafica' => $id_parent,
            'nome' => post('nome'),
            'mansione' => post('mansione'),
            'telefono' => post('telefono'),
            'email' => post('email'),
            'idsede' => post('idsede'),
        ], ['id' => $id_record]);

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'deletereferente':
        $dbo->query('DELETE FROM `an_referenti` WHERE `id`='.prepare($id_record));
        $dbo->query('UPDATE co_preventivi SET idreferente = 0 WHERE `idreferente` = '.prepare($id_record));

        flash()->info(tr('Referente eliminato!'));

        break;
}
