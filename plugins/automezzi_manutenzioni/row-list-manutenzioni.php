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

// Recupero le manutenzioni per questo automezzo
$manutenzioni = $dbo->fetchArray('
    SELECT
        m.*
    FROM
        an_automezzi_scadenze m
    WHERE
        m.idsede = '.prepare($id_record).'
        AND m.is_manutenzione = 1
    ORDER BY
        m.data_inizio DESC
');

echo '
<table class="table table-striped table-hover table-sm">
    <thead>
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th width="15%" class="text-center">'.tr('Data').'</th>
            <th width="15%" class="text-center">'.tr('Km').'</th>
            <th width="10%" class="text-center">'.tr('Allegati').'</th>
            <th width="10%" class="text-center">'.tr('Completato').'</th>
            <th width="12%" class="text-center">'.tr('Azioni').'</th>
        </tr>
    </thead>
    <tbody>';

if (!empty($manutenzioni)) {
    $disabled = $user->gruppo == 'Tecnici' ? 'disabled' : '';
    foreach ($manutenzioni as $manutenzione) {
        $n_file = $dbo->fetchNum('SELECT * FROM zz_files WHERE id_record='.$manutenzione['id'].' AND id_plugin='.$id_plugin);
        echo '
        <tr>
            <td>'.$manutenzione['descrizione'].'</td>
            <td class="text-center">'.Translator::dateToLocale($manutenzione['data_inizio']).'</td>
            <td class="text-center">'.(!empty($manutenzione['km']) ? number_format($manutenzione['km'], 0, ',', '.').' km' : '-').'</td>
            <td class="text-center">'.($n_file ? $n_file.' <i class="text-info fa fa-file"></i>' : '').'</td>
            <td class="text-center">'.($manutenzione['is_completato'] ? '<i class="text-success fa fa-check"></i>' : '<i class="text-muted fa fa-times"></i>').'</td>
            <td class="text-center">
                <button class="btn btn-xs btn-warning" data-href="'.$plugin->fileurl('modals/manage_manutenzione.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id='.$id_record.'&idmanutenzione='.$manutenzione['id'].'" data-card-widget="modal" data-title="'.tr('Modifica manutenzione').'" '.$disabled.'>
                    <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-xs btn-danger ask" data-backto="record-edit" data-op="delscadenza" data-id="'.$manutenzione['id'].'" data-id_plugin="'.$id_plugin.'" '.$disabled.'>
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>';
    }
} else {
    echo '
        <tr>
            <td colspan="6" class="text-center">
                <i class="fa fa-info-circle"></i> '.tr('Nessuna manutenzione registrata').'
            </td>
        </tr>';
}

echo '
    </tbody>
</table>';
