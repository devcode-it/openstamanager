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
        $predefined = post('predefined');

        if (empty($dbo->fetchArray('SELECT * FROM `dt_porto` LEFT JOIN `dt_porto_lang` ON (`dt_porto_lang`.`id_record` = `dt_porto`.`id` AND `dt_porto_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `name`='.prepare($descrizione).' AND `dt_porto`.`id`!='.prepare($id_record)) == 0)) {
            if (!empty($predefined)) {
                $dbo->query('UPDATE `dt_porto` SET `predefined` = 0');
            }

            $dbo->update('dt_porto', [
                'predefined' => $predefined,
            ], ['id' => $id_record]);

            $dbo->update('dt_porto_lang', [
                'name' => $descrizione,
            ], ['id_record' => $id_record, 'id_lang' => Models\Locale::getDefault()->id]);

            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr("E' già presente un Porto con questa descrizione"));
        }
        break;

    case 'add':
        $descrizione = filter('descrizione');

            if (empty($dbo->fetchArray('SELECT `dt_porto`.`id` FROM `dt_porto` LEFT JOIN `dt_porto_lang` ON (`dt_porto_lang`.`id_record` = `dt_porto`.`id` AND `dt_porto_lang`.`id_lang` = '.Models\Locale::getDefault()->id.') WHERE `name`='.prepare($descrizione)))) {
            $dbo->insert('dt_porto', [
                'created_at' => 'NOW()',
            ]);
            $id_record = $dbo->lastInsertedID();

            $dbo->insert('dt_porto_lang', [
                'name' => $descrizione,
                'id_record' => $id_record,
                'id_lang' => Models\Locale::getDefault()->id,
            ]);

            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'porto',
            ]));
        } else {
            flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione", [
                '_TYPE_' => 'porto',
            ]));
        }

        break;

    case 'delete':
        $documenti = $dbo->fetchNum('SELECT `id` FROM `dt_ddt` WHERE `idporto`='.prepare($id_record).'
            UNION SELECT `id` FROM `co_documenti` WHERE `idporto`='.prepare($id_record).'
            UNION SELECT `id` FROM `co_preventivi` WHERE `idporto`='.prepare($id_record));

        if (isset($id_record) && empty($documenti)) {
            $dbo->query('DELETE FROM `dt_porto` WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'porto',
            ]));
        } else {
            flash()->error(tr('Sono presenti dei documenti collegati a questo porto.'));
        }

        break;
}
