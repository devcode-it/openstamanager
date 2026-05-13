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
    case 'addprovvigione':
        $dbo->insert('co_provvigioni', [
            'id_articolo' => $id_parent,
            'id_agente' => post('id_agente'),
            'provvigione' => post('provvigione'),
            'tipo_provvigione' => post('tipo_provvigione'),
        ]);
        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Aggiunta nuova provvigione!'));

        break;

    case 'updateprovvigione':
        $dbo->update('co_provvigioni', [
            'id_agente' => post('id_agente'),
            'provvigione' => post('provvigione'),
            'tipo_provvigione' => post('tipo_provvigione'),
        ], ['id' => $id_record]);

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'deleteprovvigione':
        $dbo->delete('co_provvigioni', ['id' => $id_record]);

        flash()->info(tr('Provvigione eliminata!'));

        break;
}
