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
    $csv = new $import_selezionato($record->local_filepath);
    $fields = $csv->getAvailableFields();

    // Generazione della base per i campi da selezionare
    $campi_obbligatori = [];
    $campi_opzionali = [];

    foreach ($fields as $key => $value) {
        $label = $value['label'];
        $is_required = isset($value['required']) && $value['required'];

        // Aggiungi un indicatore per i campi obbligatori
        if ($is_required) {
            $label .= ' *';
        }

        $campo = [
            'id' => $key + 1,
            'text' => $label,
        ];

        // Separa i campi obbligatori da quelli opzionali
        if ($is_required) {
            $campi_obbligatori[] = $campo;
        } else {
            $campi_opzionali[] = $campo;
        }

        if ($value['primary_key']) {
            $primary_key = $key + 1;
        }
    }

    // Caso speciale per anagrafiche: telefono e partita IVA (almeno uno dei due è obbligatorio)
    foreach ($fields as $key => $value) {
        if (($value['field'] === 'telefono' || $value['field'] === 'piva')
            && isset($value['required']) && $value['required'] === false) {
            // Sposta questi campi tra quelli obbligatori
            foreach ($campi_opzionali as $index => $campo) {
                if ($campo['id'] === $key + 1) {
                    // Aggiungi l'indicatore che è parte di un gruppo OR (usando un asterisco semplice)
                    $campo['text'] .= ' *';
                    $campi_obbligatori[] = $campo;
                    unset($campi_opzionali[$index]);
                    break;
                }
            }
        }
    }

    // Ordina i campi opzionali in ordine alfabetico
    usort($campi_opzionali, fn ($a, $b) => strcmp((string) $a['text'], (string) $b['text']));

    // Unisci i campi, prima gli obbligatori e poi gli opzionali ordinati alfabeticamente
    $campi_disponibili = array_merge($campi_obbligatori, array_values($campi_opzionali));

    echo '
<form action="" method="post" id="edit-form">
    <input type="hidden" name="backto" value="record-list">
    <input type="hidden" name="op" value="import">

    <div class="alert alert-info" style="background-color: #17a2b8; color: white; border: none; padding: 10px 15px;">
        <i class="fa fa-info-circle"></i> <strong>'.tr('Informazioni importanti:').'</strong>
        <div style="margin-top: 8px;">'.tr('I campi con * sono obbligatori e devono essere mappati nel file CSV.').'</div>';

    if ($import_selezionato === Modules\Anagrafiche\Import\CSV::class) {
        echo '<div style="margin-top: 8px;"><i class="fa fa-exclamation-triangle"></i> <strong>'.tr('Requisiti:').'</strong> '.tr('Mappare almeno uno tra Telefono e Partita IVA.').'</div>
            <div style="margin-top: 8px;"><i class="fa fa-magic"></i> <strong>'.tr('Automatismi:').'</strong> '.tr('I campi Tipo anagrafica e Settore merceologico vengono generati automaticamente se mappati.').'</div>';
    }

    if ($import_selezionato === Modules\Articoli\Import\CSV::class) {
        echo '<div style="margin-top: 8px;"><i class="fa fa-exclamation-triangle"></i> <strong>'.tr('Formato numeri:').'</strong> '.tr('Gli importi inseriti devono avere come separatore il punto e presentare due decimali.').'</div>
        <div style="margin-top: 8px;"><i class="fa fa-link"></i> <strong>'.tr('Associazione anagrafiche:').'</strong> '.tr('L\'importazione dell\'anagrafica collegata all\'articolo avviene utilizzando come chiave primaria la Partita IVA, e in seguito la ragione sociale se non viene trovata corrispondenza. È opportuno pertanto indicare nel file CSV di importazione la partita IVA dell\'anagrafica per associare correttamente l\'anagrafica cliente/fornitore del listino.').'</div>';
    }

    if ($import_selezionato === Modules\Interventi\Import\CSV::class) {
        echo '<div class="alert alert-info" style="background-color: #17a2b8; color: white; border: none; padding: 10px 15px;">
            <div style="margin-top: 8px;"><i class="fa fa-link"></i> <strong>'.tr('Anagrafiche:').'</strong> '.tr('La ricerca dell\'anagrafica da collegare all\'attività avviene sulla base della partita IVA o del codice fiscale mappato. Per procedere all\'importazione dell\'attività è pertanto necessario che almeno uno dei due valori siano censiti in anagrafica.').'</div>
            <div style="margin-top: 8px;"><i class="fa fa-cogs"></i> <strong>'.tr('Impianti:').'</strong> '.tr('Per procedere all\'importazione di attività collegate ad impianti è necessario che la matricola indicata sia già censita in impianti. Procedere quindi prima all\'importazione degli impianti e successivamente delle attività.').'</div>
            <div style="margin-top: 8px;"><i class="fa fa-magic"></i> <strong>'.tr('Automatismi:').'</strong> '.tr('È presente in fase di importazione un automatismo che genera automaticamente i valori dei seguenti campi se mappati: la sessione del tecnico se mappato l\'orario di inizio attività, lo stato attività se non è già presente a gestionale.').'</div>
        </div>';
    }

    if ($import_selezionato === Modules\Impianti\Import\CSV::class) {
        echo '<div class="alert alert-info" style="background-color: #17a2b8; color: white; border: none; padding: 10px 15px;">
            <div style="margin-top: 8px;"><i class="fa fa-link"></i> <strong>'.tr('Anagrafiche:').'</strong> '.tr('La ricerca dell\'anagrafica da collegare all\'impianto avviene sulla base della partita IVA o del codice fiscale mappato. Per procedere all\'importazione dell\'impianto è pertanto necessario che almeno uno dei due valori siano censiti in anagrafica.').'</div>
            <div style="margin-top: 8px;"><i class="fa fa-exclamation-triangle"></i> <strong>'.tr('Requisiti:').'</strong> '.tr('I campi Matricola e Nome sono obbligatori. Inoltre, è necessario mappare almeno uno tra Partita IVA cliente e Codice Fiscale cliente.').'</div>
            <div style="margin-top: 8px;"><i class="fa fa-magic"></i> <strong>'.tr('Automatismi:').'</strong> '.tr('Le categorie e sottocategorie vengono create automaticamente se non esistenti. Le marche vengono create automaticamente se non esistenti.').'</div>
        </div>';
    }

    if ($import_selezionato === Modules\ListiniCliente\Import\CSV::class) {
        echo '<div class="alert alert-info" style="background-color: #17a2b8; color: white; border: none; padding: 10px 15px;">
            <div style="margin-top: 8px;"><i class="fa fa-exclamation-triangle"></i> <strong>'.tr('Requisiti:').'</strong> '.tr('I campi Nome listino, Codice articolo e Prezzo unitario sono obbligatori.').'</div>
            <div style="margin-top: 8px;"><i class="fa fa-magic"></i> <strong>'.tr('Automatismi:').'</strong> '.tr('Se il listino o l\'articolo non esistono, l\'importazione verrà saltata.').'</div>
        </div>';
    }

    echo '</div><div class="alert alert-warning" style="background-color: #ffc107; color: #212529; border: none; padding: 10px 15px;">
        <i class="fa fa-warning"></i> <strong>'.tr('ATTENZIONE:').'</strong>
        <div style="margin-top: 8px;">'.tr('Per record esistenti, tutti i campi mappati sovrascriveranno i dati attuali, anche se vuoti nel CSV. Non mappare le colonne che non si desidera modificare.').'</div>
    </div>';
    echo '

    <div class="row">
        <div class="col-md-3">
            {[ "type": "checkbox", "label": "'.tr('Importa prima riga').'", "name": "include_first_row", "extra":"", "value": "1"  ]}
        </div>

        <div class="col-md-3">
            {[ "type": "checkbox", "label": "'.tr('Aggiorna record esistenti').'", "name": "update_record", "extra":"", "value": "1"  ]}
        </div>

        <div class="col-md-3">
            {[ "type": "checkbox", "label": "'.tr('Crea record mancanti').'", "name": "add_record", "extra":"", "value": "1"  ]}
        </div>

        <div class="col-md-3">
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
        $names = $value['names'] ?? [$value['label']];
        foreach ($names as $name) {
            $nomi_disponibili[$key][] = trim(string_lowercase($name));
        }
    }

    echo '
    <div class="row">';

    for ($column = 0; $column < $numero_colonne; ++$column) {
        echo '
    <div class="col-sm-6 col-lg-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">'.tr('Colonna _NUM_', [
            '_NUM_' => $column + 1,
        ]).'</h3>
            </div>

            <div class="card-body">';

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

            // Gestione errori di validazione
            if (data.error) {
                $("#main_loading").fadeOut();

                swal({
                    title: "'.tr('Errore').'",
                    text: data.message,
                    type: "error",
                });

                return;
            }

            // Aggiorna i contatori
            count += data.imported;

            // Se ci sono altre pagine da importare
            if(data.more) {
                importPage(page + 1);
            } else {
                $("#main_loading").fadeOut();

                // Prepara il messaggio di completamento
                let title = "'.tr('Importazione completata').'";
                let text = "'.tr('Righe importate: _COUNT_', [
        '_COUNT_' => '" + data.imported + "',
    ]).'";

                // Se ci sono record falliti, mostra un messaggio diverso
                let html = "";
                if (data.failed > 0) {
                    text += "<br>'.tr('Righe non importate: _COUNT_', [
        '_COUNT_' => '" + data.failed + "',
    ]).'";

                    // Aggiungi il link per scaricare il file delle anomalie
                    if (data.failed_records_path) {
                        html = "<div class=\'row\'><div class=\'col-md-12\'>" +
                            "<div class=\'alert alert-warning\'>" +
                            "<i class=\'fa fa-exclamation-triangle\'></i> '.tr('Alcune righe non sono state importate a causa di errori o campi obbligatori mancanti.').'<br>" +
                            "<a href=\'" + globals.rootdir + "/" + data.failed_records_path + "\' class=\'btn btn-info btn-sm\'>" +
                            "<i class=\'fa fa-download\'></i> '.tr('Scarica file anomalie').'" +
                            "</a>" +
                            "</div>" +
                            "</div></div>";
                    }
                }

                // Mostra il messaggio di completamento
                swal({
                    title: title,
                    text: text,
                    html: html,
                    type: data.failed > 0 ? "warning" : "success",
                    confirmButtonText: "'.tr('OK').'",
                });

                // Aggiungi la sezione per visualizzare i file importati e quelli con anomalie
                if (data.failed_records_path) {
                    // Controlla se esiste già la sezione
                    if ($("#import-results").length === 0) {
                        $("#edit-form").after("<div id=\'import-results\' class=\'row\'>" +
                            "<div class=\'col-md-12\'>" +
                            "<div class=\'box box-warning\'>" +
                            "<div class=\'box-header with-border\'>" +
                            "<h3 class=\'box-title\'><i class=\'fa fa-file-text-o\'></i> '.tr('Risultati importazione').'</h3>" +
                            "</div>" +
                            "<div class=\'box-body\'>" +
                            "<div class=\'row\'>" +
                            "<div class=\'col-md-12\'>" +
                            "<h4>'.tr('File con anomalie').'</h4>" +
                            "<ul id=\'anomalie-files\' class=\'list-group\'></ul>" +
                            "</div>" +
                            "</div>" +
                            "</div>" +
                            "</div>" +
                            "</div>" +
                            "</div>");
                    }

                    // Aggiungi il file alla lista
                    $("#anomalie-files").append("<li class=\'list-group-item\'>" +
                        "<div class=\'row\'>" +
                        "<div class=\'col-md-8\'>" + data.failed_records_path.split("/").pop() + "</div>" +
                        "<div class=\'col-md-4 text-right\'>" +
                        "<a href=\'" + globals.rootdir + "/" + data.failed_records_path + "\' class=\'btn btn-info btn-sm\'>" +
                        "<i class=\'fa fa-download\'></i> '.tr('Scarica').'" +
                        "</a> " +
                        "</div>" +
                        "</div>" +
                        "</li>");
                }
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
