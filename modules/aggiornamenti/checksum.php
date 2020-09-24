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

$file = basename(__FILE__);
$effettua_controllo = filter('effettua_controllo');

// Schermata di caricamento delle informazioni
if (empty($effettua_controllo)) {
    echo '
<div id="righe_controlli">

</div>

<div class="alert alert-info" id="box-loading">
    <i class="fa fa-spinner fa-spin"></i> '.tr('Caricamento in corso').'...
</div>

<script>
var content = $("#righe_controlli");
var loader = $("#box-loading");
$(document).ready(function () {
    loader.show();

    content.html("");
    content.load("'.$structure->fileurl($file).'?effettua_controllo=1", function() {
        loader.hide();
    });
})
</script>';

    return;
}

$contents = file_get_contents(base_dir().'/checksum.json');
$checksum = json_decode($contents);

if (empty($checksum)) {
    echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr('Impossibile effettuare controlli di integrità in assenza del file _FILE_', [
        '_FILE_' => '<b>checksum.json</b>',
    ]).'.
</div>';

    return;
}

// Controllo degli errori
$errors = [];
foreach ($checksum as $file => $md5) {
    $verifica = md5_file(base_dir().'/'.$file);
    if ($verifica != $md5) {
        $errors[] = $file;
    }
}

// Schermata di visualizzazione degli errori
if (!empty($errors)) {
    echo '
<p>'.tr("Segue l'elenco dei file che presentano checksum diverso rispetto a quello registrato nella versione ufficiale").'.</p>
<div class="alert alert-warning">
    <i class="fa fa-warning"></i>
    '.tr('Attenzione: questa funzionalità può presentare dei risultati falsamente positivi, sulla base del contenuto del file _FILE_', [
    '_FILE_' => '<b>checksum.json</b>',
]).'.
</div>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>'.tr('File con integrità errata').'</th>
        </tr>
    </thead>

    <tbody>';

    foreach ($errors as $error) {
        echo '
        <tr>
            <td>
                '.$error.'
            </td>
        </tr>';
    }

    echo '
    </tbody>
</table>';
} else {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr('Nessun file con problemi di integrità').'.
</div>';
}
