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

?>
<form action="" method="post" id="inventario-form">
    <input type="hidden" name="op" value="inventario">
    <input type="hidden" name="backto" value="record-list">

    <div class="row">
        <div class="col-md-5">
            {[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede", "ajax-source": "sedi_azienda", "value": "0", "required": 1, "id": "sede-inventario" ]}
        </div>

        <div class="col-md-5">
            {[ "type": "text", "label": "<?php echo tr('Data'); ?>", "name": "data", "value": "-now-", "required": 1 ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-5">
            {[ "type": "text", "label": "<?php echo tr('Barcode / Ricerca articolo'); ?>", "name": "barcode", "id": "barcode-inventario", "class": "text-center", "placeholder": "<?php echo tr('Scansiona barcode o digita per cercare...'); ?>" ]}
        </div>

        <div class="col-md-5">
            {[ "type": "select", "label": "<?php echo tr('Articolo'); ?>", "name": "idarticolo", "ajax-source": "articoli", "id": "articolo-inventario", "select-options": {"permetti_movimento_a_zero": 1, "idanagrafica": <?php echo setting('Azienda predefinita'); ?>, "idsede_partenza": 0, "idsede_destinazione": 0 } ]}
        </div>

        <div class="col-md-2" style="margin-top: 25px">
            <button type="button" class="btn btn-primary btn-block" onclick="aggiungiRigaInventario()">
                <i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?>
            </button>
        </div>
    </div>

    <!-- Tabella righe inventario -->
    <div class="row">
        <div class="col-md-12">
            <div id="container-righe-inventario">
                <!-- Le righe verranno aggiunte dinamicamente qui -->
            </div>
        </div>
    </div>

    <!-- PULSANTI -->
    <div class="row" id="buttons">
        <div class="col-md-12 text-center">
            <button type="button" class="btn btn-success btn-lg" onclick="salvaInventario()" id="btn-salva-inventario">
                <i class="fa fa-check"></i> <?php echo tr('Salva inventario'); ?>
            </button>
        </div>
    </div>
</form>

<div id="messages"></div>

<div class="alert alert-info hidden" id="articolo-missing">
    <i class="fa fa-exclamation-circle"></i> <?php echo tr('Nessuna corrispondenza trovata!'); ?>
</div>

<script>
let righeInventario = [];
let contatore = 0;

$(document).ready(function () {
    init();
    $("#btn-salva-inventario").hide();

    // Focus sul barcode all'apertura con delay
    setTimeout(function() {
        $("#barcode-inventario").focus();
    }, 500);
    
    // Gestione cambio sede
    $("#sede-inventario").on("change", function() {
        let sede_id = $(this).val();
        if (righeInventario.length > 0) {
            swal("<?php echo tr('Attenzione'); ?>", "<?php echo tr('Non è possibile cambiare sede con righe già inserite'); ?>", "warning");
            return false;
        }
        
        // Aggiorna le opzioni degli articoli con la nuova sede
        $("#articolo-inventario").data("select-options").idsede_partenza = sede_id;
        $("#articolo-inventario").selectReset();
    });
    
    // Gestione barcode
    $("#barcode-inventario").on("keyup", function (event) {
        if (event.which === 13) {
            let search = $(this).val().trim();
            if (search !== "") {
                ricercaBarcode(search);
            }
        }
    });

    // Gestione Enter nel campo barcode per prevenire submit
    $("#barcode-inventario").on("keydown", function (event) {
        if (event.which === 13) {
            event.preventDefault();
        }
    });
    
    // Reload pagina alla chiusura del modal
    $("#modals > div").on("hidden.bs.modal", function () {
        location.reload();
    });
});

function ricercaBarcode(barcode) {
    let sede_id = $("#sede-inventario").val();
    if (!sede_id) {
        swal("<?php echo tr('Attenzione'); ?>", "<?php echo tr('Seleziona prima una sede'); ?>", "warning");
        return;
    }
    
    // Ricerca via ajax del barcode negli articoli
    let options = $("#articolo-inventario").data("select-options");
    options.idsede_partenza = sede_id;
    
    $.get(globals.rootdir + "/ajax_select.php", {
        op: "articoli",
        search: barcode,
        options: options
    }, function(data){
        data = JSON.parse(data);
        
        if(data.results.length === 1) {
            let record = data.results[0];
            // Usa qta_sede se disponibile, altrimenti qta
            record.qta_sede = record.qta_sede !== undefined ? record.qta_sede : (record.qta || 0);

            $("#articolo-inventario").selectSetNew(record.id, record.text, record);
            $("#barcode-inventario").val("");
            aggiungiRigaInventario();
        } else if (data.results.length > 1) {
            $("#articolo-inventario").selectReset();
            $("#barcode-inventario").val("");
            swal("<?php echo tr('Attenzione'); ?>", "<?php echo tr('Trovati più articoli, seleziona quello corretto dal menu a tendina'); ?>", "info");
        } else {
            $("#articolo-missing").removeClass("hidden");
            $("#barcode-inventario").val("");
            setTimeout(function() {
                $("#articolo-missing").addClass("hidden");
            }, 3000);
        }
    });
}

function aggiungiRigaInventario() {
    let sede_id = $("#sede-inventario").val();
    let articolo_data = $("#articolo-inventario").selectData();

    if (!sede_id) {
        swal("<?php echo tr('Attenzione'); ?>", "<?php echo tr('Seleziona prima una sede'); ?>", "warning");
        return;
    }

    if (!articolo_data || !articolo_data.id) {
        swal("<?php echo tr('Attenzione'); ?>", "<?php echo tr('Seleziona un articolo'); ?>", "warning");
        return;
    }

    // Controlla se l'articolo è già presente
    let articolo_esistente = righeInventario.find(r => r.id_articolo == articolo_data.id);
    if (articolo_esistente) {
        swal("<?php echo tr('Attenzione'); ?>", "<?php echo tr('Articolo già presente nella lista'); ?>", "warning");
        return;
    }

    // Ottieni giacenza attuale per la sede selezionata
    let giacenza_attuale = articolo_data.qta_sede || 0;

    let riga = {
        id: contatore++,
        id_articolo: articolo_data.id,
        codice: articolo_data.codice || '',
        descrizione: articolo_data.descrizione || '',
        giacenza_attuale: giacenza_attuale,
        nuova_giacenza: giacenza_attuale,
        ubicazione: articolo_data.ubicazione || ''
    };

    righeInventario.push(riga);
    aggiornaTabella();

    // Reset selezione articolo
    $("#articolo-inventario").selectReset();
    $("#barcode-inventario").focus();

    // Blocca la sede se ci sono righe
    if (righeInventario.length > 0) {
        $("#sede-inventario").prop("disabled", true);
        $("#btn-salva-inventario").show();
    }
}

function rimuoviRigaInventario(id) {
    righeInventario = righeInventario.filter(r => r.id !== id);
    aggiornaTabella();

    // Sblocca la sede se non ci sono più righe
    if (righeInventario.length === 0) {
        $("#sede-inventario").prop("disabled", false);
        $("#btn-salva-inventario").hide();
    }
}

function aggiornaTabella() {
    // Invia le righe al server per generare l'HTML con la struttura corretta
    $.post(globals.rootdir + "/modules/movimenti/modals/genera_righe.php", {
        righe: righeInventario
    }, function(html) {
        $("#container-righe-inventario").html(html);
        // Reinizializza gli input dopo il caricamento
        init();
    }).fail(function() {
        console.error("Errore nel caricamento delle righe");
    });
}

function aggiornaGiacenza(id, valore) {
    console.log("Aggiorna giacenza:", id, valore);
    let riga = righeInventario.find(r => r.id === id);
    if (riga) {
        riga.nuova_giacenza = parseFloat(valore) || 0;
        console.log("Giacenza aggiornata:", riga);
    }
}

function aggiornaUbicazione(id, valore) {
    console.log("Aggiorna ubicazione:", id, valore);
    let riga = righeInventario.find(r => r.id === id);
    if (riga) {
        riga.ubicazione = valore;
        console.log("Ubicazione aggiornata:", riga);
    }
}

function salvaInventario() {
    if (righeInventario.length === 0) {
        swal("<?php echo tr('Attenzione'); ?>", "<?php echo tr('Aggiungi almeno un articolo'); ?>", "warning");
        return;
    }

    let sede_id = $("#sede-inventario").val();
    let data_inventario = new Date().toISOString().split('T')[0];

    // Prepara i dati per il salvataggio
    let dati = {
        op: 'salva_inventario',
        idsede: sede_id,
        data: data_inventario,
        righe: righeInventario
    };

    // Salvataggio via AJAX
    $.post(globals.rootdir + "/modules/movimenti/actions.php", dati, function(response) {
        if (response.success) {
            swal("<?php echo tr('Successo'); ?>", "<?php echo tr('Inventario salvato correttamente'); ?>", "success").then(function() {
                $("#modals > div").modal("hide");
            });
        } else {
            swal("<?php echo tr('Errore'); ?>", response.message || "<?php echo tr('Errore durante il salvataggio'); ?>", "error");
        }
    }, 'json').fail(function() {
        swal("<?php echo tr('Errore'); ?>", "<?php echo tr('Errore di comunicazione con il server'); ?>", "error");
    });
}
</script>
