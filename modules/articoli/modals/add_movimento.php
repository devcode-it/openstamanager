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

// Imposto come azienda l'azienda predefinita per selezionare le sedi a cui ho accesso
// select-options

?>
<form action="" method="post" id="add-form">
    <input type="hidden" name="op" value="add-movimento">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-4">
            {["type": "select", "label": "<?php echo tr('Articolo'); ?>", "name": "idarticolo", "ajax-source": "articoli", "value": "<?php echo get('id_articolo'); ?>", "required": 1, "readonly": 1,  "select-options": {"permetti_movimento_a_zero": 1, "idanagrafica": <?php echo setting('Azienda predefinita'); ?>, "idsede_partenza": 0, "idsede_destinazione": 0 } ]}
        </div>

        <div class="col-md-2">
            {["type": "number", "label": "<?php echo tr('Quantità'); ?>", "name": "qta", "decimals": "qta", "value": "1", "required": 1 ]}
        </div>

        <div class="col-md-2">
            {["type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "value": "-now-", "required": 1 ]}
        </div>

        <div class="col-md-4">
            {["type": "select", "label": "<?php echo tr('Causale'); ?>", "name": "causale", "values": "query=SELECT `mg_causali_movimenti`.`id`, `title` as text, `description` as descrizione, `tipo_movimento` FROM `mg_causali_movimenti` LEFT JOIN `mg_causali_movimenti_lang` ON (`mg_causali_movimenti`.`id` = `mg_causali_movimenti_lang`.`id_record` AND `mg_causali_movimenti_lang`.`id_lang` = <?php echo prepare(Models\Locale::getDefault()->id); ?>)", "value": 1, "required": 1 ]}
            <input type="hidden" name="tipo_movimento" id="tipo_movimento" value="carico">
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {["type": "textarea", "label": "<?php echo tr('Descrizione movimento'); ?>", "name": "movimento", "required": 1, "value": "Carico manuale" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "<?php echo tr('Sede partenza'); ?>", "name": "idsede_partenza", "ajax-source": "sedi_azienda", "value": "0", "required": 1, "disabled": "1" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "select", "label": "<?php echo tr('Sede destinazione'); ?>", "name": "idsede_destinazione", "ajax-source": "sedi_azienda", "value": "0", "required": 1 ]}
        </div>
    </div>

    <!-- PULSANTI -->
    <div class="row" id="buttons">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-exchange"></i> <?php echo tr('Movimenta'); ?>
            </button>
        </div>
    </div>
</form>
<?php

echo '
<hr>

<div id="messages"></div>

<div class="alert alert-info hidden" id="articolo-missing">
    <i class="fa fa-exclamation-circle"></i> '.tr('Nessuna corrispondenza trovata!').'
</div>

<script>

    function abilitaSede(id){
        $(id).removeClass("disabled")
            .attr("disabled", false)
            .attr("required", true);
    }

    function disabilitaSede(id){
        $(id).addClass("disabled")
            .attr("disabled", true)
            .attr("required", false);
    }

    $(document).ready(function () {
        init();

        $("#causale").on("change", function () {
            let data = $(this).selectData();
            if (data) {
                $("#movimento").val(data.descrizione);
                $("#tipo_movimento").val(data.tipo_movimento);

                if (data.tipo_movimento === "carico") {
                    disabilitaSede("#idsede_partenza");
                    abilitaSede("#idsede_destinazione");
                } else if (data.tipo_movimento === "scarico") {
                    abilitaSede("#idsede_partenza");
                    disabilitaSede("#idsede_destinazione");
                } else {
                    abilitaSede("#idsede_partenza");
                    abilitaSede("#idsede_destinazione");
                }
            } else {
                disabilitaSede("#idsede_partenza");
                disabilitaSede("#idsede_destinazione");
            }
        });

        // Reload pagina appena chiudo il modal
        $("#modals > div").on("hidden.bs.modal", function () {
            location.reload();
        });
    });

    async function salva(button) {
        $("#messages").html("");
        let qta_input = input("qta");
        let tipo_movimento = $("#tipo_movimento").val();

        let valid = await salvaForm(button, "#add-form");

        if (valid) {
            let articolo = $("#idarticolo").selectData();

            let prezzo_acquisto = parseFloat(articolo.prezzo_acquisto);
            let prezzo_vendita = parseFloat(articolo.prezzo_vendita);

            let qta_movimento = qta_input.get();

            let alert_type, icon, text, qta_rimanente;
            if (tipo_movimento === "carico") {
                alert_type = "alert-success";
                icon = "fa-arrow-up";
                text = "Carico";
                qta_rimanente = parseFloat(articolo.qta) + parseFloat(qta_movimento);
            } else if (tipo_movimento === "scarico") {
                alert_type = "alert-danger";
                icon = "fa-arrow-down";
                text = "Scarico";
                qta_rimanente = parseFloat(articolo.qta) - parseFloat(qta_movimento);
            } else if (tipo_movimento === "spostamento") {
                alert_type = "alert-info";
                icon = "fa-arrow-down";
                text = "Spostamento";
                qta_rimanente = parseFloat(articolo.qta);
            }

            if (articolo.descrizione) {
                let testo = $("#info-articolo").html();

                testo = testo.replace("|alert-type|", alert_type)
                    .replace("|icon|", icon)
                    .replace("|descrizione|", articolo.descrizione)
                    .replace("|codice|", articolo.codice)
                    .replace("|misura|", articolo.um)
                    .replace("|misura|", articolo.um)
                    .replace("|descrizione-movimento|", text)
                    .replace("|movimento|", qta_movimento.toLocale())
                    .replace("|rimanente|", qta_rimanente.toLocale())
                    .replace("|prezzo_acquisto|", prezzo_acquisto.toLocale())
                    .replace("|prezzo_vendita|", prezzo_vendita.toLocale());

                $("#messages").html(testo);
            }

            qta_input.set(1);
            $("#causale").trigger("change");

        }
    }
</script>';

if (setting('Attiva scorciatoie da tastiera')) {
    echo '
<script>
hotkeys("f8", "carico", function() {
    $("#modals > div #direzione").val(1).change();
});
hotkeys.setScope("carico");

hotkeys("f9", "scarico", function() {
    $("#modals > div #direzione").val(2).change();
});
hotkeys.setScope("scarico");
</script>';
}

echo '
<div class="hidden" id="info-articolo">
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-info text-center">
                <h3>
                    |codice|
                </h3>
                <p><b>'.tr('Descrizione').':</b> |descrizione|</p>
                <p><b>'.tr('Prezzo acquisto').':</b> |prezzo_acquisto| '.currency().'</p>
                <p><b>'.tr('Prezzo vendita').':</b> |prezzo_vendita| '.currency().'</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="alert |alert-type| text-center">
                <h3>
                    <i class="fa |icon|"></i> |descrizione-movimento| |movimento| |misura|
                    <i class="fa fa-arrow-circle-right"></i> |rimanente| |misura| rimanenti
                </h3>
            </div>
        </div>
    </div>
</div>';
