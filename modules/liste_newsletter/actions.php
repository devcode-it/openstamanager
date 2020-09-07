<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Modules\Newsletter\Lista;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
        $lista = Lista::build(filter('name'));
        $id_record = $lista->id;

        flash()->info(tr('Nuova lista newsletter creata!'));

        break;

    case 'update':
        $lista->name = filter('name');
        $lista->description = filter('description');

        $query = filter('query');
        if (check_query($query)) {
            $lista->query = html_entity_decode($query);
        }

        $lista->save();

        flash()->info(tr('Lista newsletter salvata!'));

        break;

    case 'delete':
        $lista->delete();

        flash()->info(tr('Lista newsletter rimossa!'));

        break;

    case 'add_receivers':
        $receivers = post('receivers');

        $lista->anagrafiche()->syncWithoutDetaching($receivers);

        flash()->info(tr('Aggiunti nuovi destinatari alla lista!'));

        break;

    case 'remove_receiver':
        $receiver = post('id');

        $lista->anagrafiche()->detach($receiver);

        flash()->info(tr('Destinatario rimosso dalla lista!'));

        break;
}
