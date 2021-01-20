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

use Modules\Checklists\Checklist;
use Modules\Checklists\ChecklistItem;

switch (post('op')) {
    case 'add':
        $record = Checklist::build(post('name'));

        $record->id_module = post('module') ?: null;
        $record->id_plugin = post('plugin') ?: null;
        $record->save();

        $id_record = $record->id;

        flash()->info(tr('Nuova checklist creata!'));

        break;

    case 'update':
        $record->name = post('name');

        $record->id_module = post('module') ?: null;
        $record->id_plugin = post('plugin') ?: null;
        $record->save();

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

    case 'delete':
        $record->delete();

        flash()->info(tr('Checklist eliminata!'));

        break;

    case 'add_item':
        $content = post('content');
        $parent_id = post('parent') ?: null;
        $item = ChecklistItem::build($record, $content, $parent_id);

        flash()->info(tr('Nuova riga della checklist creata!'));

        break;

    case 'delete_item':
        $item = ChecklistItem::find(post('check_id'));
        $item->delete();

        flash()->info(tr('Riga della checklist eliminata!'));

        break;

    case 'update_position':
        $order = explode(',', post('order', true));

        foreach ($order as $i => $id_riga) {
            $dbo->query('UPDATE `zz_checklist_items` SET `order` = '.prepare($i).' WHERE id='.prepare($id_riga));
        }

        break;
}
