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

use Modules\DDT\Spedizione;

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');
        $vettore = post('esterno');
        $predefined = post('predefined');

        if (isset($descrizione)) {
            $spedizione_new = Spedizione::where('id', '=', (new Spedizione())->getByField('title', $descrizione))->orWhere('name', $descrizione)->where('id', '!=', $id_record)->first();
            if (empty($spedizione_new)) {
                $spedizione->setTranslation('title', $descrizione);
                if (!empty($predefined)) {
                    $dbo->query('UPDATE `dt_spedizione` SET `predefined` = 0');
                }
                if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                    $spedizione->name = $descrizione;
                }
                $spedizione->predefined = $predefined;
                $spedizione->esterno = $vettore;
                $spedizione->save();
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente una spedizione con questo nome."));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }
        break;

    case 'add':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            if (empty(Spedizione::where('id', '=', (new Spedizione())->getByField('title', $descrizione))->orWhere('name', $descrizione)->where('id', '!=', $id_record)->first())) {
                $spedizione = Spedizione::build();
                if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                    $spedizione->name = $descrizione;
                }
                $spedizione->save();

                $id_record = $dbo->lastInsertedID();
                $spedizione->setTranslation('title', $descrizione);
                $spedizione->save();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunta nuova spedizione _NAME_', [
                    '_NAME_' => $descrizione,
                ]));
            } else {
                flash()->error(tr("E' già presente un spedizione con questo nome."));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':
        $documenti = $dbo->fetchNum('SELECT `id` FROM `dt_ddt` WHERE `idspedizione`='.prepare($id_record).'
            UNION SELECT `id` FROM `co_documenti` WHERE `idspedizione`='.prepare($id_record));

        if ((!empty($id_record)) && empty($documenti)) {
            $dbo->query('DELETE FROM `dt_spedizione` WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'spedizione',
            ]));
        } else {
            flash()->error(tr('Sono presenti dei documenti collegati a questo spedizione.'));
        }

        break;
}
