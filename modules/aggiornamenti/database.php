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

function integrity_diff($expected, $current)
{
    foreach ($expected as $key => $value) {
        if (array_key_exists($key, $current) && is_array($value)) {
            if (!is_array($current[$key])) {
                $difference[$key] = $value;
            } else {
                $new_diff = integrity_diff($value, $current[$key]);
                if (!empty($new_diff)) {
                    $difference[$key] = $new_diff;
                }
            }
        } elseif (!array_key_exists($key, $current) || $current[$key] != $value) {
            $difference[$key] = [
                'current' => $current[$key],
                'expected' => $value,
            ];
        }
    }

    return !isset($difference) ? [] : $difference;
}

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

$contents = file_get_contents(base_dir().'/database.json');
$data = json_decode($contents, true);

if (empty($data)) {
    echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr('Impossibile effettuare controlli di integrità in assenza del file _FILE_', [
            '_FILE_' => '<b>database.json</b>',
        ]).'.
</div>';

    return;
}

// Controllo degli errori
$info = Update::getDatabaseStructure();
$results = integrity_diff($data, $info);

// Schermata di visualizzazione degli errori
if (!empty($results)) {
    echo '
<p>'.tr("Segue l'elenco delle tabelle del database che presentano una struttura diversa rispetto a quella prevista nella versione ufficiale del gestionale").'.</p>
<div class="alert alert-warning">
    <i class="fa fa-warning"></i>
    '.tr('Attenzione: questa funzionalità può presentare dei risultati falsamente positivi, sulla base del contenuto del file _FILE_', [
            '_FILE_' => '<b>database.json</b>',
        ]).'.
</div>';

    foreach ($results as $table => $errors) {
        echo '
<h3>'.$table.'</h3>';

        if (array_key_exists('current', $errors) && $errors['current'] == null) {
            echo '
<p>'.tr('Tabella assente').'</p>';
            continue;
        }

        $foreign_keys = $errors['foreign_keys'] ?: [];
        unset($errors['foreign_keys']);

        if (!empty($errors)) {
            echo '
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>'.tr('Colonna').'</th>
            <th>'.tr('Conflitto').'</th>
        </tr>
    </thead>

    <tbody>';

            foreach ($errors as $name => $diff) {
                echo '
        <tr>
            <td>
                '.$name.'
            </td>
            <td>
                '.json_encode($diff).'
            </td>
        </tr>';
            }

            echo '
    </tbody>
</table>';
        }

        if (!empty($foreign_keys)) {
            echo '
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>'.tr('Foreign keys').'</th>
            <th>'.tr('Conflitto').'</th>
        </tr>
    </thead>

    <tbody>';

            foreach ($foreign_keys as $name => $diff) {
                echo '
        <tr>
            <td>
                '.$name.'
            </td>
            <td>
                '.json_encode($diff).'
            </td>
        </tr>';
            }

            echo '
    </tbody>
</table>';
        }
    }
} else {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr('Il database non presenta problemi di integrità').'.
</div>';
}
