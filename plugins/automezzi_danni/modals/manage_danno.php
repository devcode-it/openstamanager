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

include_once __DIR__.'/../../../core.php';

$idautomezzo = get('id');
$iddanno = get('iddanno');

// Recupero dati danno se in modifica
if (!empty($iddanno)) {
    $record = $dbo->fetchOne('SELECT * FROM an_automezzi_danni WHERE id = '.prepare($iddanno));
    $title = tr('Modifica');
    $button_icon = 'edit';
} else {
    $record = [];
    $title = tr('Aggiungi');
    $button_icon = 'plus';
}

echo '
<form action="" method="post" id="form-danno">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="'.(!empty($iddanno) ? 'editdanno' : 'adddanno').'">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_record" value="'.$idautomezzo.'">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="iddanno" value="'.$iddanno.'">

    <div class="row">
        <div class="col-md-4">
            {[ "type": "date", "label": "'.tr('Data').'", "name": "data", "required": 1, "value": "-now-", "data-edit-value": "'.($record['data'] ?? '').'" ]}
        </div>
        <div class="col-md-8">
            {[ "type": "text", "label": "'.tr('Luogo').'", "name": "luogo", "required": 1, "value": "'.($record['dove'] ?? '').'" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {[ "type": "text", "label": "'.tr('Descrizione').'", "name": "descrizione", "required": 1, "value": "'.($record['descrizione'] ?? '').'" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary"><i class="fa fa-'.$button_icon.'"></i> '.$title.'</button>
        </div>
    </div>
</form>';
if ($record['id']) {
    echo '
    <hr>
    {( "name": "filelist_and_upload", "id_module": "'.$id_module.'", "id_record": "'.$iddanno.'", "id_plugin": "'.$id_plugin.'" )}';
}

echo '
<script>
$(document).ready(function() {
    // Se siamo in modalità edit, aggiorna il valore del campo data
    var editValue = $("input[name=\'data\']").data("edit-value");
    if (editValue) {
        $("input[name=\'data\']").val(editValue);
    }

    init();
});
</script>';
