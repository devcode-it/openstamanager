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
        $is_bloccata = filter('is_bloccata');

        if (isset($descrizione)) {
            $esistente = $dbo->fetchArray('SELECT `an_relazioni`.`id` FROM `an_relazioni` LEFT JOIN `an_relazioni_lang` ON (`an_relazioni`.`id`=`an_relazioni_lang`.`id_record` AND `an_relazioni_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `an_relazioni_lang`.`title`='.prepare($descrizione).' AND `an_relazioni`.`id` != '.prepare($id_record));
            if (empty($esistente)) {
                $dbo->query('UPDATE `an_relazioni` SET `colore`='.prepare($colore).', `is_bloccata`='.prepare($is_bloccata).' WHERE `id`='.prepare($id_record));
                $dbo->query('UPDATE `an_relazioni_lang` SET `title`='.prepare($descrizione).' WHERE `id_record`='.prepare($id_record));
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente una relazione '_NAME_'.", [
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
        $is_bloccata = filter('is_bloccata');

        if (isset($descrizione)) {
            $esistente = $dbo->fetchArray('SELECT `an_relazioni`.`id` FROM `an_relazioni` LEFT JOIN `an_relazioni_lang` ON (`an_relazioni`.`id`=`an_relazioni_lang`.`id_record` AND `an_relazioni_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `an_relazioni_lang`.`title`='.prepare($descrizione));
            if (empty($esistente)) {
                $dbo->query('INSERT INTO `an_relazioni` (`colore`, `is_bloccata`) VALUES ('.prepare($colore).', '.prepare($is_bloccata).' )');
                $id_record = $dbo->lastInsertedID();
                $dbo->query('INSERT INTO `an_relazioni_lang` (`title`, `id_record`, `id_lang`) VALUES ('.prepare($descrizione).', '.prepare($id_record).', '.prepare(Models\Locale::getDefault()->id).')');

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
        $dbo->query('UPDATE `an_relazioni` SET `deleted_at`=NOW() WHERE `id`='.prepare($id_record));
        flash()->info(tr('Relazione _NAME_ eliminata con successo!', [
            '_NAME_' => $descrizione,
        ]));

        break;
}
