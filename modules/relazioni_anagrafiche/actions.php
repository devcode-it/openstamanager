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
            if ($dbo->fetchNum('SELECT * FROM `an_relazioni` WHERE `descrizione`='.prepare($descrizione).' AND `id`!='.prepare($id_record)) == 0) {
                $dbo->query('UPDATE `an_relazioni` SET `descrizione`='.prepare($descrizione).', `colore`='.prepare($colore).' WHERE `id`='.prepare($id_record));
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente una relazione _NAME_.", [
                    '_TYPE_' => $descrizione,
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
            if ($dbo->fetchNum('SELECT * FROM `an_relazioni` WHERE `descrizione`='.prepare($descrizione)) == 0) {
                $dbo->query('INSERT INTO `an_relazioni` (`descrizione`, `colore` ) VALUES ('.prepare($descrizione).', '.prepare($colore).' )');

                $id_record = $dbo->lastInsertedID();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunta nuova relazione _NAME_', [
                    '_NAME_' => $descrizione,
                ]));
            } else {
                flash()->error(tr("E' già presente una relazione di _NAME_.", [
                    '_NAME_' => $descrizione,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':
        $righe = $dbo->fetchNum('SELECT idanagrafica FROM an_anagrafiche WHERE idrelazione='.prepare($id_record));

        if (isset($id_record) && empty($righe)) {
            $dbo->query('DELETE FROM `an_relazioni` WHERE `id`='.prepare($id_record));
            flash()->info(tr('Relazione _NAME_ eliminata con successo!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            flash()->error(tr('Sono presenti '.count($righe).' anagrafiche collegate a questa relazione.'));
        }

        break;
}
