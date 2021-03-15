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

use Modules\Importazione\Import;

include_once __DIR__.'/../../core.php';

// Gestione del redirect in caso di caricamento del file
if (filter('op')) {
    return;
}

if (empty($id_record)) {
    require base_dir().'/add.php';
} else {
    $import = Import::find($id_record);
    $import_selezionato = $import->class;

    // Inizializzazione del lettore CSV
    $csv = new $import_selezionato($record->filepath);
    $fields = $csv->getAvailableFields();

    // Generazione della base per i campi da selezionare
    $campi_disponibili = [];
    foreach ($fields as $key => $value) {
        $campi_disponibili[] = [
            'id' => $key + 1,
            'text' => $value['label'],
        ];

        if ($value['primary_key']) {
            $primary_key = $key + 1;
        }
    }

    echo '
<form action="" method="post" id="edit-form">
    <input type="hidden" name="backto" value="record-list">
    <input type="hidden" name="op" value="import">

    <div class="row">
        <div class="col-md-8">
            {[ "type": "checkbox", "label": "'.tr('Importa prima riga').'", "name": "include_first_row", "extra":"", "value": "1"  ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Chiave primaria').'", "name": "primary_key", "values": '.json_encode($campi_disponibili).', "value": "'.$primary_key.'" ]}
        </div>
    </div>';

    // Lettura delle prime righe disponibili
    $righe = $csv->getRows(0, 10);
    $prima_riga = $csv->getHeader();
    $numero_colonne = count($prima_riga);

    // Trasformazione dei nomi indicati per i campi in lowercase
    $nomi_disponibili = [];
    foreach ($fields as $key => $value) {
        $nomi_disponibili[$key] = [];
        $names = isset($value['names']) ? $value['names'] : [$value['label']];
        foreach ($names as $name) {
            $nomi_disponibili[$key][] = trim(string_lowercase($name));
        }
    }

    echo '
    <div class="row">';

    for ($column = 0; $column < $numero_colonne; ++$column) {
        echo '
    <div class="col-sm-6 col-lg-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">'.tr('Colonna _NUM_', [
                    '_NUM_' => $column + 1,
                ]).'</h3>
            </div>

            <div class="panel-body">';

        // Individuazione delle corrispondenze
        $selezionato = null;
        foreach ($fields as $key => $value) {
            // Confronto per l'individuazione della relativa colonna
            $nome = trim(string_lowercase($prima_riga[$column]));
            if (in_array($nome, $nomi_disponibili[$key])) {
                $escludi_prima_riga = 1;
                $selezionato = $key + 1;
                break;
            }
        }

        echo '
                {[ "type": "select", "label": "'.tr('Campo').'", "name": "fields['.$column.']", "values": '.json_encode($campi_disponibili).', "value": "'.$selezionato.'" ]}

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>'.tr('#').'</th>
                            <th>'.tr('Valore').'</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($righe as $key => $row) {
            echo '
                        <tr>
                            <td>'.($key + 1).'</td>
                            <td>'.$row[$column].'</td>
                        </tr>';
        }

        echo '

                    </tbody>
                </table>

            </div>
        </div>
    </div>';
    }

    echo '
    </div>
</form>';

    echo '
<script>
var count = 0;
$(document).ready(function() {';

    if ($escludi_prima_riga) {
        echo '
    $("#include_first_row").prop("checked", false).trigger("change");';
    }

    echo '
    $("#save-buttons").find(".dropdown-menu, .dropdown-toggle").remove();

    var save = $("#save");
    save.html("<i class=\"fa fa-flag-checkered\"></i> '.tr('Avvia importazione').'");

    save.unbind("click");
    save.on("click", function() {
        count = 0;
        importPage(0);
    });
});

function importPage(page) {
    // Ricerca della chiave primaria tra i campi selezionati
    let primary_key = input("primary_key").get();
    if (primary_key) {
        let primary_key_found = false;
        $("[name^=fields]").each(function() {
            primary_key_found = primary_key_found || (input(this).get() == primary_key);
        });

        if (!primary_key_found) {
            swal({
                title: "'.tr('Chiave primaria selezionata non presente tra i campi').'",
                type: "error",
            });

            return;
        }
    }

    $("#main_loading").show();

    let data = {
        id_module: "'.$id_module.'",
        id_plugin: "'.$id_plugin.'",
        id_record: "'.$id_record.'",
        page: page,
    };

    $("#edit-form").ajaxSubmit({
        url: globals.rootdir + "/actions.php",
        data: data,
        type: "post",
        success: function(data) {
            data = JSON.parse(data);

            count += data.count;

            if(data.more) {
                importPage(page + 1);
            } else {
                $("#main_loading").fadeOut();

                swal({
                    title: "'.tr('Importazione completata: _COUNT_  righe processate', [
                        '_COUNT_' => '" + count + "',
                    ]).'",
                    type: "success",
                });
            }
        },
        error: function(data) {
            $("#main_loading").fadeOut();

            alert("'.tr('Errore').': " + data);
        }
    });
};
</script>';
}
