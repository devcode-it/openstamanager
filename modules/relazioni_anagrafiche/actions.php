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

use Modules\Anagrafiche\Relazione;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');
        $colore = filter('colore');
        $is_bloccata = filter('is_bloccata');

        if (isset($descrizione)) {
            $relazione_new = Relazione::where('id', '=', (new Relazione())->getByField('title', $descrizione))->where('id', '!=', $id_record)->first();
            if (empty($relazione_new)) {
                $relazione->setTranslation('title', $descrizione);
                if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                    $relazione->name = $descrizione;
                }
                $relazione->colore = $colore;
                $relazione->is_bloccata = $is_bloccata;
                $relazione->save();
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente una relazione _NAME_.", [
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
        $is_bloccata = filter('is_bloccata_add');

        if (isset($descrizione)) {
            if (empty(Relazione::where('id', '=', (new Relazione())->getByField('title', $descrizione))->where('id', '!=', $id_record)->first())) {
                $relazione = Relazione::build();
                if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                    $relazione->name = $descrizione;
                }
                $relazione->colore = $colore;
                $relazione->is_bloccata = $is_bloccata;
                $relazione->save();
                $id_record = $dbo->lastInsertedID();
                $relazione->setTranslation('title', $descrizione);
                $relazione->save();

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
