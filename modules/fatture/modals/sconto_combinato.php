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

// Contenuto del modal per il calcolo dello sconto combinato
echo '
<form id="form-sconto-combinato">
    <div class="row">
        <div class="offset-md-4 col-md-4">
            {[ "type": "text", "label": "'.tr('Sconto/magg. combinato').'", "name": "prc_combinato", "icon-after": "%", "class": "math-mask text-right", "help": "'.tr('Esempio: 2+2+2 viene convertito in 2% di sconto con 2% aggiuntivo sul totale scontato e 2% di maggiorazione sul totale finale').'. '.tr('Sono ammessi i segni + e -').'" ]}
        </div>
    </div>
</form>

<div class="row">
    <div class="col-md-12 text-right">
        <button type="button" class="btn btn-success" id="btn-confirm-sconto"><i class="fa fa-check"></i> '.tr('Conferma').'</button>
    </div>
</div>

<script>
$(document).ready(function() {
    var modal = $("#modals > div:last");

    // Determina quale campo sconto è presente (sconto_percentuale o sconto)
    var sconto_field = $("#sconto_percentuale").length ? $("#sconto_percentuale") : $("#sconto");
    var label_sconto = $("#label_sconto");

    // Reinizializza i componenti del form quando il modal si apre
    modal.on("shown.bs.modal", function() {
        restart_inputs();
    });

    // Gestisci il click del pulsante di conferma
    $("#form-sconto-combinato").on("submit", function(e) {
        e.preventDefault();
        var prc_combinato = $("#prc_combinato").val();

        if (prc_combinato) {
            // Calcola lo sconto combinato
            $.ajax({
                url: globals.rootdir + "/ajax.php",
                type: "POST",
                data: {
                    op: "calcola_sconto_combinato",
                    prc_combinato: prc_combinato
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        sconto_field.val(response.sconto.toLocale());
                        $("#sconto_percentuale_combinato").val(prc_combinato);
                        label_sconto.removeClass("hidden");
                        label_sconto.text("'.tr('Sconto combinato').': " + prc_combinato);
                        sconto_field.trigger("keyup");
                        modal.modal("hide");
                    } else {
                        alert(response.error);
                    }
                }
            });
        } else {
            alert("'.tr('Inserire uno sconto').'");
        }
    });

    // Gestisci il click del pulsante di conferma
    modal.find("#btn-confirm-sconto").click(function() {
        $("#form-sconto-combinato").submit();
    });
});
</script>';

