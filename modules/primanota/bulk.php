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

switch (post('op')) {
    case 'export_bulk':
        $_SESSION['superselect']['mastrini'] = $id_records;

        $print = Prints::getModulePredefinedPrint($id_module);
        header('location: '.$rootdir.'/pdfgen.php?id_print='.$print['id'].'&id_record='.$id_records[0]);
        exit;
}

return [
    'export_bulk' => [
        'text' => '<span><i class="fa fa-file-o"></i> '.tr('Esporta PDF'),
        'data' => [
            'title' => tr('Vuoi davvero esportare il PDF?'),
            'msg' => '',
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ],
];
