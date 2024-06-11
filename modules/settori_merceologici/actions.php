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

use Modules\Anagrafiche\Settore;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            $settore_new = Settore::where('id', '=', (new Settore())->getByField('title', $descrizione))->orWhere('name', $descrizione)->where('id', '!=', $id_record)->first();
            if (empty($settore_new)) {
                $settore->setTranslation('title', $descrizione);
                if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                    $settore->name = $descrizione;
                }
                $settore->save();
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente una settore _NAME_.", [
                    '_NAME_' => $descrizione,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;
    case 'add':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            if (empty(Settore::where('id', '=', (new Settore())->getByField('title', $descrizione))->orWhere('name', $descrizione)->where('id', '!=', $id_record)->first())) {
                $settore = Settore::build();
                if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                    $settore->name = $descrizione;
                }
                $settore->save();

                $id_record = $dbo->lastInsertedID();
                $settore->setTranslation('title', $descrizione);
                $settore->save();

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

        if ((!empty($id_record)) && empty($righe)) {
            $dbo->query('DELETE FROM `an_settori` WHERE `id`='.prepare($id_record));
            flash()->info(tr('Settore merceologico _NAME_ eliminato con successo!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            flash()->error(tr('Sono presenti '.count($righe).' anagrafiche collegate a questo settore merceologico.'));
        }

        break;
}
