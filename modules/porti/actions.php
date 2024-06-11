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
use Modules\DDT\Porto;

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');
        $predefined = post('predefined');

        if (isset($descrizione)) {
            $porto_new = Porto::where('id', '=', (new Porto())->getByField('title', $descrizione))->orWhere('name', $descrizione)->where('id', '!=', $id_record)->first();
            if (empty($porto_new)) {
                $porto->setTranslation('title', $descrizione);
                if (!empty($predefined)) {
                    $dbo->query('UPDATE `dt_porto` SET `predefined` = 0');
                }
                if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                    $porto->name = $descrizione;
                }
                $porto->predefined = $predefined;
                $porto->save();
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente un porto con questo nome."));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            if (empty(Porto::where('id', '=', (new Porto())->getByField('title', $descrizione))->orWhere('name', $descrizione)->where('id', '!=', $id_record)->first())) {
                $porto = Porto::build();
                if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                    $porto->name = $descrizione;
                }
                $porto->save();

                $id_record = $dbo->lastInsertedID();
                $porto->setTranslation('title', $descrizione);
                $porto->save();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunta nuova porto _NAME_', [
                    '_NAME_' => $descrizione,
                ]));
            } else {
                flash()->error(tr("E' già presente un porto con questo nome."));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':
        $documenti = $dbo->fetchNum('SELECT `id` FROM `dt_ddt` WHERE `idporto`='.prepare($id_record).'
            UNION SELECT `id` FROM `co_documenti` WHERE `idporto`='.prepare($id_record).'
            UNION SELECT `id` FROM `co_preventivi` WHERE `idporto`='.prepare($id_record));

        if ((!empty($id_record)) && empty($documenti)) {
            $dbo->query('DELETE FROM `dt_porto` WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'porto',
            ]));
        } else {
            flash()->error(tr('Sono presenti dei documenti collegati a questo porto.'));
        }

        break;
}
