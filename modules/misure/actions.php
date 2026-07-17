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

use Models\UnitaMisura;

switch (filter('op')) {
    case 'update':
        $valore = filter('valore');

        if (isset($valore)) {
            $exists = UnitaMisura::where('valore', $valore)->where('id', '!=', $id_record)->exists();
            if (!$exists) {
                UnitaMisura::find($id_record)->update(['valore' => $valore]);
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con lo stesso valore.", [
                    '_TYPE_' => 'unità di misura',
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'add':
        $valore = filter('valore');

        if (isset($valore)) {
            $exists = UnitaMisura::where('valore', $valore)->exists();
            if (!$exists) {
                $unita = UnitaMisura::create(['valore' => $valore]);
                $id_record = $unita->id;

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $valore, 'text' => $valore]);
                }

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'unità di misura',
                ]));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con lo stesso valore.", [
                    '_TYPE_' => 'unità di misura',
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':
        $valore = $record['valore'];
        $has_righe = Modules\Fatture\Components\Riga::where('um', $valore)->exists() ||
                     Modules\DDT\Components\Riga::where('um', $valore)->exists() ||
                     Modules\Ordini\Components\Riga::where('um', $valore)->exists() ||
                     Modules\Contratti\Components\Riga::where('um', $valore)->exists() ||
                     Modules\Articoli\Articolo::where('um', $valore)->exists() ||
                     Modules\Preventivi\Components\Riga::where('um', $valore)->exists();

        if ((!empty($id_record)) && !$has_righe) {
            UnitaMisura::find($id_record)->delete();
            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'unità di misura',
            ]));
        } else {
            flash()->error(tr('Sono presenti righe collegate a questa unità di misura.'));
        }

        break;
}
