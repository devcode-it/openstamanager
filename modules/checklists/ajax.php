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

include_once __DIR__.'/../../core.php';
use Modules\Checklists\Check;
use Modules\Checklists\ChecklistItem;

switch(post('op')){

    case "delete_check":
        $id = post('id');
        $main_check = post('main_check');

        if($main_check){
            $record = ChecklistItem::find($id);
        }else{
            $record = Check::find($id);
        }

        $record->delete();

        break;

    case "update_position":

        $main_check = post('main_check');
        $order = explode(',', post('order', true));

        if($main_check){
            foreach($order as $i => $id){
                $dbo->query("UPDATE zz_checklist_items SET `order`=".prepare($i)." WHERE id=".prepare($id));
            }
        }else{
            foreach($order as $i => $id){
                $dbo->query("UPDATE zz_checks SET `order`=".prepare($i)." WHERE id=".prepare($id));
            }
        }

        break;

    case "save_checkbox":

        $id = post('id');

        $record = Check::find($id);
        $record->checked_by = $user->id;
        $record->checked_at = date('Y-m-d H:i:s');
        $record->save();

        break;

    case "remove_checkbox":

        $id = post('id');

        $record = Check::find($id);
        $record->checked_by = NULL;
        $record->checked_at = NULL;
        $record->save();

        break;

    case "edit_check":
        $id_record = post('id_record');
        $main_check = post('main_check');

        if($main_check){
            $record = ChecklistItem::find($id_record);
        }else{
            $record = Check::find($id_record);
        }

        $record->content = post('content');
        $record->save();

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;
}

?>