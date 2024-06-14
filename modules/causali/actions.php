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
use Modules\DDT\Causale;

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            $causale_new = Causale::where('id', '=', (new Causale())->getByField('title', $descrizione))->orWhere('name', $descrizione)->where('id', '!=', $id_record)->first();
            if (empty($causale_new)) {
                $causale->setTranslation('title', $descrizione);
                if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                    $causale->name = $descrizione;
                }
                $causale->predefined = post('predefined');
                $causale->is_importabile = filter('is_importabile');
                $causale->reversed = filter('reversed');
                $causale->is_rientrabile = filter('is_rientrabile');
                $causale->save();
                flash()->info(tr('Salvataggio completato!'));
            } else {
                flash()->error(tr("E' già presente una causale di trasporto con la stessa descrizione"));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            if (empty(Causale::where('id', '=', (new Causale())->getByField('title', $descrizione))->where('id', '!=', $id_record)->first())) {
                $causale = Causale::build();
                if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                    $causale->name = $descrizione;
                }
                $causale->save();

                $id_record = $dbo->lastInsertedID();
                $causale->setTranslation('title', $descrizione);
                $causale->save();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunta nuova causale di trasporto.'));
            } else {
                flash()->error(tr("E' già presente una causale di trasporto con la stessa descrizione"));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':
        $documenti = $dbo->fetchNum('SELECT `id` FROM `dt_ddt` WHERE `idcausalet`='.prepare($id_record).' UNION SELECT `id` FROM `co_documenti` WHERE `idcausalet`='.prepare($id_record));

        if ((!empty($id_record)) && empty($documenti)) {
            $dbo->query('DELETE FROM `dt_causalet` WHERE `id`='.prepare($id_record));
        } else {
            $dbo->update('dt_causalet', [
                'deleted_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id_record]);
        }

        flash()->info(tr('Causale di trasporto eliminata.'));

        break;
}
