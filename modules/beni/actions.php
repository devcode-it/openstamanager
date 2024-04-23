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
use Modules\DDT\AspettoBeni;

switch (post('op')) {
    case 'update':
        $descrizione = post('descrizione');

        if (isset($descrizione)) {
            $aspetto_new = AspettoBeni::where('id', '=', (new AspettoBeni())->getByField('title', $descrizione))->where('id', '!=', $id_record)->first();
            if (empty($aspetto_new)) {
                $aspetto->setTranslation('title', $descrizione);
                if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                    $aspetto->name = $descrizione;
                }
                $aspetto->save();
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente un aspetto beni con questa descrizione."));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'add':
        $descrizione = post('descrizione');

        if (isset($descrizione)) {
            if (empty(AspettoBeni::where('id', '=', (new AspettoBeni())->getByField('title', $descrizione))->where('id', '!=', $id_record)->first())) {
                $aspetto = AspettoBeni::build();
                if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                    $aspetto->name = $descrizione;
                }
                $aspetto->save();

                $id_record = $dbo->lastInsertedID();
                $aspetto->setTranslation('title', $descrizione);
                $aspetto->save();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunto nuovo Aspetto beni.'));
            } else {
                flash()->error(tr("E' già presente un aspetto beni con questa descrizione."));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':
        $documenti = $dbo->fetchNum('SELECT `id` FROM `dt_ddt` WHERE `idaspettobeni`='.prepare($id_record).' UNION SELECT `id` FROM `co_documenti` WHERE `idaspettobeni`='.prepare($id_record));

        if ((!empty($id_record)) && empty($documenti)) {
            $dbo->query('DELETE FROM `dt_aspettobeni` WHERE `id`='.prepare($id_record));
            flash()->info(tr('Aspetto beni eliminato con successo.'));
        } else {
            flash()->error(tr('Sono presenti dei documenti collegati a questo aspetto beni.'));
        }

        break;
}
