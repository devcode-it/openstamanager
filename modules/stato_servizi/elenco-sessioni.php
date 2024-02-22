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

if (setting('Attiva notifica di presenza utenti sul record')){

echo '
<table class="table table-hover table-condensed">
    <thead>
        <tr>
            <th>'.tr('Utente').'</th>
            <th class="text-center">'.tr('Record').'</th>
            <th class="text-center">'.tr('Data e ora accesso').'</th>
            <th class="text-center">'.tr('Tempo trascorso').'</th>
            <th class="text-center">'.tr('Permanenza').'</th>
        </tr>
    </thead>';

$sessioni = $dbo->fetchArray('SELECT zz_semaphores.*, SUBSTRING_INDEX(posizione, ",", -1) AS id_record, zz_modules.name AS modulo, TIMESTAMPDIFF(SECOND, zz_semaphores.created_at, zz_semaphores.updated_at) AS permanenza
FROM zz_semaphores
    INNER JOIN zz_modules ON SUBSTRING_INDEX(posizione, ",", 1) = zz_modules.id
    INNER JOIN zz_users ON zz_semaphores.id_utente = zz_users.id
ORDER BY `modulo` ASC, SUBSTRING_INDEX(posizione, ",", -1) ASC');

$gruppi = collect($sessioni)->groupBy('modulo');
foreach ($gruppi as $modulo => $sessioni) {
    echo '
    <thead>
        <tr>
            <th colspan="6" class="text-center text-muted" >'.$modulo.'</th>
        </tr>
    </thead>

    <tbody>';

    foreach ($sessioni as $sessione) {
        $class ='info';
        
        $utente = Models\User::find($sessione['id_utente']);

        echo '
            <tr class="'.$class.'" data-id="'.$sessione['id'].'" data-nome='.json_encode($sessione['name']).'>
                <td>
                    '.(($utente->photo) ? "<img class='attachment-img tip' title=".$utente->nome_completo." src=".$utente->photo.">" : "<i class='fa fa-user-circle-o attachment-img tip' title=".$utente->nome_completo."></i>").'<span class="direct-chat-name"> '.$utente->nome_completo.'</span>
                </td>
             
                <td class="text-center">
                    '.$sessione['id_record'].'
                </td>

                <td class="text-center">
                '.Translator::timestampToLocale($sessione['created_at']).'
                </td>

                <td class="text-center">
                <span class="tip" title="'.Translator::timestampToLocale($sessione['updated_at']).'" >'.Carbon\Carbon::parse($sessione['updated_at'])->diffForHumans().'</span>
                </td>

                <td class="text-center">
                <span class="tip" title="'.Translator::timestampToLocale($sessione['updated_at']).'" >'.gmdate('H:i:s', $sessione['permanenza']).'</span>
                </td>

            </tr>';
    }
}
echo '
    </tbody>
</table>';

}else{
    echo '<span>Non è possibile monitorare la presenta degli utenti.</span>';
}

