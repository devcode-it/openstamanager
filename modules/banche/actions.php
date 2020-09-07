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

switch (filter('op')) {
    case 'update':
        $nome = filter('nome');

        if (isset($nome)) {
            $array = [
                'nome' => $nome,
                'filiale' => post('filiale'),
                'iban' => post('iban'),
                'bic' => post('bic'),
                'id_pianodeiconti3' => post('id_pianodeiconti3'),
                'note' => post('note'),
            ];

            if (!empty($id_record)) {
                $dbo->update('co_banche', $array, ['id' => $id_record]);
            }

            flash()->info(tr('Salvataggio completato.'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'add':
        $nome = filter('nome');
        $bic = filter('bic');
        $iban = filter('iban');

        if (isset($nome)) {
            $dbo->query('INSERT INTO `co_banche` (`nome`, `bic`, `iban`) VALUES ('.prepare($nome).', '.prepare($bic).', '.prepare($iban).')');
            $id_record = $dbo->lastInsertedID();

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $nome]);
            }

            flash()->info(tr('Aggiunta nuova  _TYPE_', [
                '_TYPE_' => 'banca',
            ]));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'delete':
        $dbo->update('co_banche', [
            'deleted_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id_record]);

        flash()->info(tr('_TYPE_ eliminata con successo!', [
            '_TYPE_' => 'Banca',
        ]));

        break;
}
