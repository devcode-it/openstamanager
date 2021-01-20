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

echo '
<div class="tip" data-toggle="tooltip" title="'.tr('Tenta la compilazione automatica delle informazioni delle fattura elettronica sulla base delle precedenti fatture del Fornitore').'.">
    <button type="button" class="btn btn-primary" '.(!empty($anagrafica) ? '' : 'disabled').' id="compilazione_automatica" onclick="compile(this)" >
        <i class="fa fa-address-book"></i> '.tr('Compila automaticamente').'
    </button>
</div>

<div class="tip" data-toggle="tooltip" title="'.tr('Tenta il completamento automatico dei riferimenti per le righe delle fattura elettronica sulla base di Ordini e DDT registrati nel gestionale per il Fornitore').'.">
    <button type="button" class="btn btn-primary" '.(!empty($anagrafica) ? '' : 'disabled').' onclick="compilaRiferimenti(this)" >
        <i class="fa fa-list"></i> '.tr('Cerca riferimenti').'
    </button>
</div>

<script>
$(document).ready(function() {
    var btn = $("#compilazione_automatica");

    if (!$("#compilazione_automatica").not("disabled")) {
        btn.click();
    }
});

function compile(btn) {
    let restore = buttonLoading(btn);

    $.ajax({
        url: globals.rootdir + "/actions.php",
        cache: false,
        type: "GET",
        dataType: "json",
        data: {
            id_module: "'.$id_module.'",
            id_plugin: "'.$id_plugin.'",
            id_record: "'.$id_record.'",
            op: "compile",
        },
        success: function(response) {
            buttonRestore(btn, restore);
            if (response.length === 0){
                return;
            }

            $("#id_tipo").selectSet(response.id_tipo);
            $("#pagamento").selectSetNew(response.pagamento.id, response.pagamento.descrizione);

            $("select[name^=iva]").each(function(){
                var aliquota = $(this).closest("tr").find("[id^=aliquota]").text();
                if (response.iva[aliquota] !== undefined){
                    $(this).selectSet(response.iva[aliquota].id);
                }
            });

            $("select[name^=conto]").each(function(){
                $(this).selectSetNew(response.conto.id, response.conto.descrizione);
            });
        },
        error: function(data) {
            swal("'.tr('Errore').'", "'.tr('La compilazione automatica dei campi non è andata a buon fine').'.", "error");

            buttonRestore(btn, restore);
        }
    });
}

function compilaRiferimenti(btn) {
    let restore = buttonLoading(btn);

    $.ajax({
        url: globals.rootdir + "/actions.php",
        cache: false,
        type: "GET",
        dataType: "json",
        data: {
            id_module: "'.$id_module.'",
            id_plugin: "'.$id_plugin.'",
            id_record: "'.$id_record.'",
            op: "riferimenti-automatici",
        },
        success: function(response) {
            buttonRestore(btn, restore);
            if (response.length === 0){
                return;
            }

            for (const [id_riga, data] of response.entries()) {
                // Selezione dinamica
                $("#selezione_riferimento" + id_riga).selectSetNew(data.documento.id, data.documento.opzione);

                // Impostazione del riferiment
                impostaRiferimento(id_riga, data.documento, data.riga);
            }
        },
        error: function(data) {
            swal("'.tr('Errore').'", "'.tr('La ricerca automatica dei riferimenti per le righe non è andata a buon fine').'.", "error");

            buttonRestore(btn, restore);
        }
    });
}
</script>';
