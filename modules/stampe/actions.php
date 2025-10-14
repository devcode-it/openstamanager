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
        if (!empty(intval(post('predefined'))) && !empty(post('module'))) {
            $dbo->query('UPDATE `zz_prints` SET `predefined` = 0 WHERE `id_module` = '.post('module'));
        }
        $print->options = post('options');
        $print->order = post('order');
        $print->predefined = intval(post('predefined'));
        $print->save();

        $print->setTranslation('title', post('title'));
        $print->setTranslation('filename', post('filename'));

        // Gestione file allegati
        $dbo->delete('zz_files_print', ['id_print' => $id_record]);
        $id_files = !empty(post('id_files')) ? post('id_files') : [];
        foreach ($id_files as $id_file) {
            $dbo->insert('zz_files_print', [
                'id_print' => $id_record,
                'id_file' => $id_file,
            ]);
        }

        flash()->info(tr('Modifiche salvate correttamente'));

        break;
}
