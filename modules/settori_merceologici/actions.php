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

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');
        $colore = filter('colore');

        if (isset($descrizione)) {
            if ($dbo->fetchNum('SELECT * FROM `an_settori` LEFT JOIN `an_settori_lang` ON (`an_settori`.`id` = `an_settori_lang`.`id_record` AND `an_settori_lang`.`id_lang` = '.prepare(\App::getLang()).') WHERE `name`='.prepare($descrizione).' AND `an_settori`.`id`!='.prepare($id_record)) == 0) {
                $dbo->query('UPDATE `an_settori_lang` SET `name`='.prepare($descrizione).' WHERE `id_record`='.prepare($id_record));
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente il settore merceologico _NAME_.", [
                    '_NAME_' => $descrizione,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');
        $colore = filter('colore');

        if (isset($descrizione)) {
            if ($dbo->fetchNum('SELECT * FROM `an_settori` LEFT JOIN `an_settori_lang` ON (`an_settori`.`id` = `an_settori_lang`.`id_record` AND `an_settori_lang`.`id_lang` = '.prepare(\App::getLang()).') WHERE `name`='.prepare($descrizione)) == 0) {
                $dbo->query('INSERT INTO `an_settori` (`id`, `created_at`, `updated_at`) VALUES (NULL, NOW(), NOW())');
                $id_record = $dbo->lastInsertedID();
                $dbo->query('INSERT INTO `an_settori_lang` (`name`, `id_record`, `id_lang`) VALUES ('.prepare($descrizione).', '.prepare($id_record).', '.prepare(\App::getLang()).')');

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunto nuovo settore merceologico _NAME_', [
                    '_NAME_' => $descrizione,
                ]));
            } else {
                flash()->error(tr("E' già presente un settore merceologico _NAME_.", [
                    '_NAME_' => $descrizione,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':
        $righe = $dbo->fetchNum('SELECT idanagrafica FROM an_anagrafiche WHERE id_settore='.prepare($id_record));

        if (isset($id_record) && empty($righe)) {
            $dbo->query('DELETE FROM `an_settori` WHERE `id`='.prepare($id_record));
            flash()->info(tr('Settore merceologico _NAME_ eliminato con successo!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            flash()->error(tr('Sono presenti '.count($righe).' anagrafiche collegate a questo settore merceologico.'));
        }

        break;
}
