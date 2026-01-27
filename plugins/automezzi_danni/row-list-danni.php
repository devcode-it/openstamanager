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

// Recupero i danni per questo automezzo
$danni = $dbo->fetchArray('
    SELECT
        m.*
    FROM
        an_automezzi_danni m
    WHERE
        m.idsede = '.prepare($id_record).'
    ORDER BY
        m.data DESC
');

echo '
<table class="table table-striped table-hover table-sm">
    <thead>
        <tr>
            <th width="15%" class="text-center">'.tr('Data').'</th>
            <th width="25%">'.tr('Luogo').'</th>
            <th>'.tr('Descrizione').'</th>
            <th width="12%" class="text-center">'.tr('Azioni').'</th>
        </tr>
    </thead>
    <tbody>';

if (!empty($danni)) {
    $disabled = $user->gruppo == 'Tecnici' ? 'disabled' : '';
    foreach ($danni as $danno) {
        $n_file = $dbo->fetchNum('SELECT * FROM zz_files WHERE id_record='.$danno['id'].' AND id_plugin='.$id_plugin);
        echo '
        <tr>
            <td class="text-center">'.Translator::dateToLocale($danno['data']).'</td>
            <td>'.$danno['luogo'].'</td>
            <td>'.$danno['descrizione'].'</td>
            <td class="text-center">
                <button class="btn btn-xs btn-warning" data-href="'.$plugin->fileurl('modals/manage_danno.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id='.$id_record.'&iddanno='.$danno['id'].'" data-card-widget="modal" data-title="'.tr('Modifica danno').'" '.$disabled.'>
                    <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-xs btn-danger ask" data-backto="record-edit" data-op="deldanno" data-id="'.$danno['id'].'" data-id_plugin="'.$id_plugin.'" '.$disabled.'>
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>';
    }
} else {
    echo '
        <tr>
            <td colspan="6" class="text-center">
                <i class="fa fa-info-circle"></i> '.tr('Nessun danno registrato').'
            </td>
        </tr>';
}

echo '
    </tbody>
</table>';
