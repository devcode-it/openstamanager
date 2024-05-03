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
        $nome = post('name');
        $tag_new = $dbo->fetchOne('SELECT * FROM `in_tags` WHERE `in_tags`.`name`='.prepare($nome));

        if (!empty($tag_new) && $tag_new['id'] != $id_record) {
            flash()->error(tr('Tag _NAME_ già esistente!', [
                '_NAME_' => $nome,
            ]));
        } else {
            $record->nome = $nome;
            $record->save();

            flash()->info(tr('Informazioni salvate correttamente!'));
        }

        break;

    case 'add':
        $nome = post('name');
        $tag_new = $dbo->fetchOne('SELECT * FROM `in_tags` WHERE `in_tags`.`name`='.prepare($nome));

        if (!empty($tag_new) && $tag_new['id'] != $id_record) {
            flash()->error(tr('Tag _NAME_ già esistente!', [
                '_NAME_' => $nome,
            ]));
        } else {
            $record = $dbo->insert('in_tags', [
                'name' => $nome,
            ]);
            $id_record = $dbo->lastInsertedID();

            flash()->info(tr('Nuovo tag aggiunto!'));
        }
       
        break;

    case 'delete':
        $dbo->query('DELETE `in_tags` WHERE `in_tags`.`id`='.prepare($id_record));

        flash()->info(tr('Tag eliminato!'));

        break;
}
