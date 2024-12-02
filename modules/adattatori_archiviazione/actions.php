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

use Modules\FileAdapters\FileAdapter;

switch (filter('op')) {
    case 'add':
        $adapter = new FileAdapter();

        $adapter->name = post('name');
        $adapter->class = '\\Modules\\FileAdapters\\Adapters\\'.post('class');

        $adapter->save();

        $id_record = $adapter->id;

        flash()->info(tr('Nuoovo adattatore aggiunto!'));

        break;

    case 'update':
        $adapter->name = post('name');
        $adapter->class = '\\Modules\\FileAdapters\\Adapters\\'.post('class');
        $adapter->options = post('options');

        if (post('is_default') == 1) {
            $dbo->query('UPDATE `zz_storage_adapters` SET `is_default` = 0');
            $adapter->is_default = post('is_default');
        }

        $adapter->save();

        flash()->info(tr('Adattatore modificato correttamente!'));

        break;

    case 'delete':
        $adapter->delete();

        break;
}
