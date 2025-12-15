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

// Recupero le scadenze per questo automezzo
$scadenze = $dbo->fetchArray('
    SELECT 
        s.*
    FROM 
        an_automezzi_scadenze s
    WHERE 
        s.idsede = '.prepare($id_record).'
        AND s.is_manutenzione = 0
    ORDER BY 
        s.data_inizio DESC
');

echo '
<table class="table table-striped table-hover table-sm">
    <thead>
        <tr>
            <th width="20%">'.tr('Descrizione').'</th>
            <th width="13%" class="text-center">'.tr('Inizio').'</th>
            <th width="12%" class="text-center">'.tr('Fine').'</th>
            <th width="12%" class="text-center">'.tr('Km').'</th>
            <th width="14%" class="text-center">'.tr('Codice').'</th>
            <th width="10%" class="text-center">'.tr('Allegati').'</th>
            <th width="10%" class="text-center">'.tr('Completato').'</th>
            <th width="10%" class="text-center">'.tr('Azioni').'</th>
        </tr>
    </thead>
    <tbody>';

if (!empty($scadenze)) {
    $disabled = $user->gruppo == 'Tecnici' ? 'disabled' : '';
    foreach ($scadenze as $scadenza) {
        $n_file = $dbo->fetchNum('SELECT * FROM zz_files WHERE id_record='.$scadenza['id'].' AND id_plugin='.$id_plugin);
        echo '
        <tr>
            <td>'.$scadenza['descrizione'].'</td>
            <td class="text-center">'.Translator::dateToLocale($scadenza['data_inizio']).'</td>
            <td class="text-center">'.(!empty($scadenza['data_fine']) ? Translator::dateToLocale($scadenza['data_fine']) : '-').'</td>
            <td class="text-center">'.(!empty($scadenza['km']) ? number_format($scadenza['km'], 0, ',', '.').' km' : '-').'</td>
            <td class="text-center">'.(!empty($scadenza['codice']) ? $scadenza['codice'] : '-').'</td>
            <td class="text-center">'.($n_file ? $n_file.' <i class="text-info fa fa-file"></i>' : '').'</td>
            <td class="text-center">'.($scadenza['is_completato'] ? '<i class="text-success fa fa-check"></i>' : '<i class="text-muted fa fa-times"></i>').'</td>

            <td class="text-center">
                <button class="btn btn-xs btn-warning" data-href="'.$plugin->fileurl('modals/manage_scadenza.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id='.$id_record.'&idscadenza='.$scadenza['id'].'" data-card-widget="modal" data-title="'.tr('Modifica scadenza').'" '.$disabled.'>
                    <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-xs btn-danger ask" data-backto="record-edit" data-op="delscadenza" data-id_plugin="'.$id_plugin.'" data-id="'.$scadenza['id'].'" '.$disabled.'>
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>';
    }
} else {
    echo '
        <tr>
            <td colspan="8" class="text-center">
                <i class="fa fa-info-circle"></i> '.tr('Nessuna scadenza registrata').'
            </td>
        </tr>';
}

echo '
    </tbody>
</table>';

