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

switch (post('op')) {
    case 'delete_check':
        $id = post('id');
        $main_check = post('main_check');

        if ($main_check) {
            $record = ChecklistItem::find($id);
        } else {
            $record = Check::find($id);
        }
        $record->delete();

        break;

    case 'update_position':
        $main_check = post('main_check');
        $order = explode(',', post('order', true));

        if ($main_check) {
            foreach ($order as $i => $id) {
                $dbo->query('UPDATE zz_checklist_items SET `order`='.prepare($i).' WHERE id='.prepare($id));
            }
        } else {
            foreach ($order as $i => $id) {
                $dbo->query('UPDATE zz_checks SET `order`='.prepare($i).' WHERE id='.prepare($id));
            }
        }

        break;

    case 'save_checkbox':
        $id = post('id');

        $record = Check::find($id);
        $record->checked_by = $user->id;
        $record->checked_at = date('Y-m-d H:i:s');
        $record->save();

        break;

    case 'save_note':
        $note = post('note');
        $id = post('id');

        $record = Check::find($id);
        $record->note = $note;
        $record->save();

        flash()->info(tr('Nota salvata correttamente!'));

        break;

    case 'remove_checkbox':
        $id = post('id');

        $record = Check::find($id);
        $record->checked_by = null;
        $record->checked_at = null;
        $record->save();

        break;

    case 'edit_check':
        $id_record = post('id_record');
        $main_check = post('main_check');

        if ($main_check) {
            $record = ChecklistItem::find($id_record);
        } else {
            $record = Check::find($id_record);
        }

        // Upload file
        if (!empty($_FILES) && !empty($_FILES['immagine']['name'])) {
            $upload = Uploads::upload($_FILES['immagine'], [
                'name' => 'Immagine',
                'id_category' => null,
                'id_module' => $record->id_module,
                'id_record' => $record->id_record,
            ], [
                'thumbnails' => true,
            ]);
            $id_immagine = $upload->id;

            if (!empty($id_immagine)) {
                $record->id_immagine = $id_immagine;
            } else {
                flash()->warning(tr("Errore durante il caricamento dell'immagine!"));
            }
        }

        // Eliminazione file
        if (!empty(post('delete_immagine'))) {
            $file = Models\Upload::find($record->id_immagine);

            Uploads::delete($file->filename, [
                'id_module' => $record->id_module,
                'id_record' => $record->id_record,
            ]);

            $record->id_immagine = null;
        }

        $record->content = post('content');
        $record->is_titolo = post('is_titolo');
        $record->save();

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;
}
