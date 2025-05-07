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

if (setting('Attiva notifica di presenza utenti sul record')) {
    echo '
<table class="table table-hover table-sm mb-0">
    <thead>
        <tr>
            <th width="25%">'.tr('Modulo').'</th>
            <th width="15%">'.tr('Record').'</th>
            <th width="30%">'.tr('Data e ora accesso').'</th>
            <th width="30%">'.tr('Ultimo aggiornamento').'</th>
            <!--th>'.tr('Permanenza').'</th-->
        </tr>
    </thead>';

    $sessioni = $dbo->fetchArray('SELECT
        `zz_semaphores`.*,
        SUBSTRING_INDEX(`posizione`, ",", -1) AS id_record,
        `zz_modules_lang`.`title` AS modulo,
        TIMESTAMPDIFF(SECOND, `zz_semaphores`.`created_at`, `zz_semaphores`.`updated`) AS permanenza,
        `zz_users`.`username` AS utente
    FROM
        `zz_semaphores`
        INNER JOIN `zz_modules` ON SUBSTRING_INDEX(`posizione`, ",", 1) = `zz_modules`.`id`
        LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        INNER JOIN `zz_users` ON `zz_semaphores`.`id_utente` = `zz_users`.`id`
    ORDER BY
        `utente` ASC, SUBSTRING_INDEX(`posizione`, ",", -1) ASC');

    $gruppi = collect($sessioni)->groupBy('utente');
    $i = 0;
    foreach ($gruppi as $utente => $sessioni) {
        $utente = Models\User::find($sessioni[$i]['id_utente']);
        ++$i;

        echo '
    <thead>
        <tr>
            <th colspan="4" class="text-center bg-light"><small>
                '.($utente->photo ? '<img class="attachment-img tip mr-1" title="'.$utente->username.'" src="'.$utente->photo.'" style="max-height:20px;">' : '<i class="fa fa-user-circle-o mr-1 tip" title="'.$utente->username.'"></i>').'
                <strong>'.$utente->anagrafica->ragione_sociale.'</strong> <span class="badge badge-info">'.$utente->gruppo.'</span>
            </small></th>
        </tr>
    </thead>

    <tbody>';

        foreach ($sessioni as $sessione) {
            $class = 'info';

            echo '
            <tr class="'.($class === 'info' ? '' : 'table-'.$class).'" data-id="'.$sessione['id'].'" data-nome='.json_encode($sessione['name']).'>
                <td>
                   '.$sessione['modulo'].'
                </td>

                <td>
                    <span class="badge badge-secondary">'.$sessione['id_record'].'</span>
                </td>

                <td>
                    <small>'.Translator::timestampToLocale($sessione['created_at']).'</small>
                </td>

                <td>
                    <span class="tip" title="'.Translator::timestampToLocale($sessione['updated']).'" ><i class="fa fa-clock-o mr-1 text-muted"></i>'.Carbon\Carbon::parse($sessione['updated'])->diffForHumans().'</span>
                </td>

                <!--td>
                <span class="tip" title="'.Translator::timestampToLocale($sessione['updated']).'" >'.gmdate('H:i:s', $sessione['permanenza']).'</span>
                </td-->

            </tr>';
        }
    }
    echo '
    </tbody>
</table>';
} else {
    echo '<div class="alert alert-info m-3">
        <i class="fa fa-info-circle mr-2"></i>'.tr('Non è possibile monitorare la presenza degli utenti, attiva l\'impostazione di notifica di presenza utenti sul record').'.
    </div>';
}
