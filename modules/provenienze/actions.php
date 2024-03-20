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
            if ($dbo->fetchNum('SELECT * FROM `an_provenienze` LEFT JOIN `an_provenienze_lang` ON (`an_provenienze`.`id` = `an_provenienze_lang`.`id_record` AND `an_provenienze_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).') WHERE `name`='.prepare($descrizione).' AND `an_provenienze`.`id`!='.prepare($id_record)) == 0) {
                $dbo->query('UPDATE `an_provenienze_lang` SET `name`='.prepare($descrizione).' WHERE `id_record` = '.prepare($id_record));
                $dbo->query('UPDATE `an_provenienze` SET `colore`='.prepare($colore).' WHERE `id`='.prepare($id_record));
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente una provenienza _NAME_.", [
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
            if ($dbo->fetchNum('SELECT * FROM `an_provenienze` LEFT JOIN `an_provenienze_lang` ON (`an_provenienze`.`id` = `an_provenienze_lang`.`id_record` AND `an_provenienze_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).') WHERE `name`='.prepare($descrizione)) == 0) {
                $dbo->query('INSERT INTO `an_provenienze` (`colore`) VALUES ('.prepare($colore).')');
                $id_record = $dbo->lastInsertedID();
                $dbo->query('INSERT INTO `an_provenienze_lang` (`name`, `id_record`, `id_lang`) VALUES ('.prepare($descrizione).', '.prepare($id_record).', '.prepare(\Models\Locale::getDefault()->id).')');

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunta nuova provenienza _NAME_', [
                    '_NAME_' => $descrizione,
                ]));
            } else {
                flash()->error(tr("E' già presente una provenienza di _NAME_.", [
                    '_NAME_' => $descrizione,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':
        $righe = $dbo->fetchNum('SELECT idanagrafica FROM an_anagrafiche WHERE id_provenienza='.prepare($id_record));

        if (isset($id_record) && empty($righe)) {
            $dbo->query('DELETE FROM `an_provenienze` WHERE `id`='.prepare($id_record));
            flash()->info(tr('Provenienza _NAME_ eliminata con successo!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            flash()->error(tr('Sono presenti '.count($righe).' anagrafiche collegate a questa provenienza.'));
        }

        break;
}
