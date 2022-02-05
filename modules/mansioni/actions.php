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

switch (post('op')) {
    case 'update':
        $nome = post('nome');

        if ($dbo->fetchNum('SELECT * FROM `an_mansioni` WHERE `nome`='.prepare($nome).' AND `id`!='.prepare($id_record)) == 0) {
            $dbo->query('UPDATE `an_mansioni` SET `nome`='.prepare($nome).' WHERE `id`='.prepare($id_record));
            flash()->info(tr('Salvataggio completato.'));
        } else {
            flash()->error(tr("E' già presente una _TYPE_ con lo stesso nome", [
                '_TYPE_' => 'mansione',
            ]));
        }

        break;

    case 'add':
        $nome = post('nome');

        if ($dbo->fetchNum('SELECT * FROM `an_mansioni` WHERE `nome`='.prepare($nome)) == 0) {
           
            $dbo->insert('an_mansioni', [
                'nome' => $nome,
            ]);

            $id_record = $dbo->lastInsertedID();

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $nome]);
            }

            flash()->info(tr('Aggiunta nuova _TYPE_', [
                '_TYPE_' => 'mansione',
            ]));
        } else {
            flash()->error(tr("E' già presente una _TYPE_ con lo stesso nome", [
                '_TYPE_' => 'mansione',
            ]));
        }

        break;

    case 'delete':
        $referenti = $dbo->fetchNum('SELECT id FROM an_referenti WHERE idmansione='.prepare($id_record));

        if (isset($id_record) && empty($referenti)) {
            $dbo->query('DELETE FROM `an_mansioni` WHERE `id`='.prepare($id_record));
            flash()->info(tr('_TYPE_ eliminata con successo.', [
                '_TYPE_' => 'Mansione',
            ]));
        } else {
            flash()->error(tr('Sono presenti dei referenti collegati a questa mansione.'));
        }

        break;
}
