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

        Models\Field::find($id_record)->update([
            'id_module' => $module,
            'id_plugin' => $plugin,
            'name' => post('name'),
            'html_name' => post('html_name'),
            'content' => post('content'),
            'on_add' => post('on_add'),
            'top' => post('top'),
        ]);

        flash()->info(tr('Salvataggio completato'));

        break;

    case 'add':
        $plugin = post('plugin_id_add') ?: null;
        $module = $plugin ? null : post('module_id_add');

        $field = Models\Field::create([
            'id_module' => $module,
            'id_plugin' => $plugin,
            'name' => post('name_add'),
            'content' => post('content_add'),
            'html_name' => secure_random_string(8),
        ]);
        $id_record = $field->id;

        flash()->info(tr('Nuovo campo personalizzato creato'));

        break;

    case 'delete':
        Models\Field::find($id_record)->delete();
        Models\FieldRecord::where('id_field', $id_record)->delete();

        flash()->info(tr('Campo personalizzato eliminato'));

        break;
}
