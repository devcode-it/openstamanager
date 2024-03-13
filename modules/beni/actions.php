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
        $descrizione = post('descrizione');

        if ($dbo->fetchNum('SELECT * FROM `dt_aspettobeni` LEFT JOIN `dt_aspettobeni_lang` ON (`dt_aspettobeni`.`id`=`dt_aspettobeni_lang`.`id_record` AND `dt_aspettobeni_lang`.`lang`='.prepare(\App::getLang()).') WHERE `name`='.prepare($descrizione).' AND `dt_aspettobeni`.`id`!='.prepare($id_record)) == 0) {
            $dbo->query('UPDATE `dt_aspettobeni_lang` SET `name`='.prepare($descrizione).' WHERE `id_record`='.prepare($id_record)).' AND `lang`='.prepare(\App::getLang());
            flash()->info(tr('Salvataggio completato.'));
        } else {
            flash()->error(tr("E' già presente un aspetto beni con questa descrizione."));
        }
        break;

    case 'add':
        $descrizione = post('descrizione');

        if ($dbo->fetchNum('SELECT * FROM `dt_aspettobeni_lang` WHERE `name`='.prepare($descrizione)) == 0) {
            $dbo->query('INSERT INTO `dt_aspettobeni` (`created_at`) VALUES (NOW())');
            $id_record = $dbo->lastInsertedID();

            $dbo->query('INSERT INTO `dt_aspettobeni_lang` (`name`, `id_record`, `id_lang`) VALUES ('.prepare($descrizione).', '.prepare($id_record).', '.prepare(\App::getLang()).')');

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $descrizione]);
            }

            flash()->info(tr('Aggiunto nuovo Aspetto beni.'));
        } else {
            flash()->error(tr("E' già presente un aspetto beni con questa descrizione."));
        }

        break;

    case 'delete':
        $documenti = $dbo->fetchNum('SELECT `id` FROM `dt_ddt` WHERE `idaspettobeni`='.prepare($id_record).' UNION SELECT `id` FROM `co_documenti` WHERE `idaspettobeni`='.prepare($id_record));

        if (isset($id_record) && empty($documenti)) {
            $dbo->query('DELETE FROM `dt_aspettobeni` WHERE `id`='.prepare($id_record));
            flash()->info(tr('Aspetto beni eliminato con successo.'));
        } else {
            flash()->error(tr('Sono presenti dei documenti collegati a questo aspetto beni.'));
        }

        break;
}
