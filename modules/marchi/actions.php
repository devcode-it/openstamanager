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

use Modules\Articoli\Marchio;

switch (post('op')) {
    // Aggiorno informazioni di base marchio
    case 'update':
        $nome = filter('name');
        $link = filter('link');

        $marchio_new = Marchio::where('name', '=', $nome)->first();

        if (!empty($marchio_new)) {
            flash()->error(tr('Questo nome è già stato utilizzato per un altro marchio.'));
        } else {
            $marchio = Marchio::find($id_record);
            $marchio->name = $nome;
            $marchio->link = $link;
            $marchio->save();

            flash()->info(tr('Marchio aggiornato!'));
        }

        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record, 'text' => $nome]);
        }

        break;

        // Aggiungo marchio
    case 'add':
        $nome = filter('name');
        $link = filter('link');

        $marchio_new = Marchio::where('name', '=', $nome)->first();

        if (!empty($marchio_new)) {
            flash()->error(tr('Questo nome è già stato utilizzato per un altro marchio.'));
        } else {
            $marchio = Marchio::build($nome);
            $id_record = $dbo->lastInsertedID();
            $marchio->link = $link;
            $marchio->save();

            flash()->info(tr('Aggiunto nuovo marchio'));
        }

        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record, 'text' => $nome]);
        }

        break;

        // Rimuovo marchio
    case 'delete':
        $dbo->query('DELETE FROM mg_marchi WHERE id='.prepare($id_record));

        flash()->info(tr('Marchio eliminato!'));
        break;
}
