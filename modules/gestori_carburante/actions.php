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

use Modules\Automezzi\Gestore;
use Modules\Automezzi\Rifornimento;

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            $exists = Gestore::where('descrizione', $descrizione)->where('id', '!=', $id_record)->exists();
            if (!$exists) {
                Gestore::find($id_record)->update(['descrizione' => $descrizione]);
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente un gestore con questa descrizione."));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            $exists = Gestore::where('descrizione', $descrizione)->exists();
            if (!$exists) {
                $gestore = Gestore::create(['descrizione' => $descrizione]);
                $id_record = $gestore->id;

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunto nuovo gestore'));
            } else {
                flash()->error(tr("E' già presente un gestore con questa descrizione."));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':
        $has_rifornimenti = Rifornimento::where('id_gestore', $id_record)->exists();

        if ((!empty($id_record)) && !$has_rifornimenti) {
            Gestore::find($id_record)->delete();

            flash()->info(tr('Gestore eliminato con successo!'));
        } else {
            flash()->error(tr('Sono presenti rifornimenti collegati a questo gestore.'));
        }

        break;
}
