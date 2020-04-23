<?php

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
    $note = $modulo->notes()->where('notification_date', '>=', date('Y-m-d'))->get();
    $notes = $notes->merge($note);
}

if (!empty($is_number_request)) {
    echo $notes->count();

    return;
}

if (empty($notes)) {
    echo '
<p>'.tr('Non ci sono note da notificare').'.</p>';

    return;
}

$moduli = $notes->groupBy('id_module')->sortBy('notification_date');
foreach ($moduli as $module_id => $note) {
    $modulo = Module::get($module_id);

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
