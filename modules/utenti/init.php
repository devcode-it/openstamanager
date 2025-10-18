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
use Models\Group;

if (!empty($id_record)) {
    $group = Group::find($id_record);

    if ($group) {
        $record = $group->toArray();
    } else {
        // Fallback: prova a ottenere i dati direttamente dal database
        $record = $dbo->fetchOne('SELECT * FROM `zz_groups` WHERE `id`='.prepare($id_record));
        if (!$record) {
            // Se il record non esiste, reindirizza alla lista
            flash()->error(tr('Gruppo non trovato'));
            redirect_url(base_path().'/controller.php?id_module='.$id_module);
        }
    }
}
