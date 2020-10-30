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

include_once __DIR__.'/../../../core.php';

use Carbon\Carbon;
use Models\Module;

if (!empty($is_title_request)) {
    echo tr('Notifiche interne');

    return;
}

$notes = collect();

$moduli = Module::getAll()->where('permission', '<>', '-');
foreach ($moduli as $modulo) {
    $note = $modulo->notes()->whereNotNull('notification_date')->orderBy('notification_date', 'asc')->get();
    $notes = $notes->merge($note);
}

if (!empty($is_number_request)) {
    echo $notes->count();

    return;
}

if ($notes->count() < 1) {
    echo '
<p>'.tr('Non ci sono note da notificare').'.</p>';

    return;
}

$moduli = $notes->groupBy('id_module')->sortBy('notification_date');
foreach ($moduli as $module_id => $note) {
    $modulo = Module::pool($module_id);

    echo '
<h4>'.$modulo->title.'</h4>
<table class="table table-hover">
    <tr>
        <th width="5%" >'.tr('Record').'</th>
        <th>'.tr('Contenuto').'</th>
        <th width="20%" class="text-center">'.tr('Data di notifica').'</th>
        <th class="text-center">#</th>
    </tr>';

    foreach ($note as $nota) {
        echo '
    <tr>
        <td>'.$nota->id_record.'</td>

        <td>
            <span class="pull-right"></span>

            '.$nota->content.'

            <small>'.$nota->user->nome_completo.'</small>
        </td>

        <td class="text-center">
            '.dateFormat($nota->notification_date).' ('.Carbon::parse($nota->notification_date)->diffForHumans().')
        </td>

        <td class="text-center">
            '.Modules::link($module_id, $nota->id_record, '', null, 'class="btn btn-primary btn-xs"', true, 'tab_note').'
        </td>
    </tr>';
    }

    echo '
</table>';
}
