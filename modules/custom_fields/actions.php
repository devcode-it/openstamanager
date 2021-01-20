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

switch (post('op')) {
    case 'update':
        $plugin = post('plugin_id') ?: null;
        $module = $plugin ? null : post('module_id');

        $dbo->update('zz_fields', [
            'id_module' => $module,
            'id_plugin' => $plugin,
            'name' => post('name'),
            'html_name' => post('html_name'),
            'content' => post('content'),
            'on_add' => post('on_add'),
            'top' => post('top'),
        ], ['id' => $id_record]);

        flash()->info(tr('Salvataggio completato'));

        break;

    case 'add':
        $plugin = post('plugin_id') ?: null;
        $module = $plugin ? null : post('module_id');

        $dbo->insert('zz_fields', [
            'id_module' => $module,
            'id_plugin' => $plugin,
            'name' => post('name'),
            'content' => post('content'),
            'html_name' => secure_random_string(8),
        ]);
        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Nuovo campo personalizzato creato'));

        break;

    case 'delete':
        $dbo->delete('zz_fields', ['id' => $id_record]);
        $dbo->delete('zz_field_record', ['id_field' => $id_record]);

        flash()->info(tr('Campo personalizzato eliminato'));

        break;
}
