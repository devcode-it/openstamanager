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
<table class="table table-hover table-condensed">
    <thead>
        <tr>
            <th>'.tr('Modulo').'</th>
            <th>'.tr('Record').'</th>
            <th>'.tr('Data e ora accesso').'</th>
            <th>'.tr('Ultimo aggiornamento').'</th>
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
            <th colspan="5" class="text-center text-muted" >'.(($utente->photo) ? "<img class='attachment-img tip' title=".$utente->username.' src='.$utente->photo.'>' : "<i class='fa fa-user-circle-o attachment-img tip' title=".$utente->username.'></i>').'<span class="direct-chat-name"> '.$utente->anagrafica->ragione_sociale.' ['.$utente->gruppo.']</span></th>
        </tr>
    </thead>

    <tbody>';

        foreach ($sessioni as $sessione) {
            $class = 'info';

            echo '
            <tr class="'.$class.'" data-id="'.$sessione['id'].'" data-nome='.json_encode($sessione['name']).'>
                <td>
                   '.$sessione['modulo'].'
                </td>
             
                <td>
                    '.$sessione['id_record'].'
                </td>

                <td>
                '.Translator::timestampToLocale($sessione['created_at']).'
                </td>

                <td>
                <span class="tip" title="'.Translator::timestampToLocale($sessione['updated']).'" >'.Carbon\Carbon::parse($sessione['updated'])->diffForHumans().'</span>
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
    echo '<span>Non è possibile monitorare la presenta degli utenti.</span>';
}
