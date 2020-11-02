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

use Models\Setting;

include_once __DIR__.'/../../core.php';

$gruppi = Setting::selectRaw('sezione AS nome, COUNT(id) AS numero')
    ->groupBy(['sezione'])
    ->orderBy('sezione')
    ->get();

echo '
<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="'.tr('Ricerca rapida').'" id="ricerca_impostazioni"/>
            <div class="input-group-btn">
                <button class="btn btn-primary" type="button">
                    <span class="fa fa-search"></span>
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <button class="btn btn-warning hidden" type="button" id="riprova_salvataggi" onclick="riprovaSalvataggio()">
            <span class="fa fa-save"></span> '.tr('Riprova salvataggi falliti').'
        </button>
    </div>
</div>

<br><hr>';

foreach ($gruppi as $key => $gruppo) {
    echo '
<!-- Impostazioni della singola sezione -->
<div class="box box-primary collapsed-box" title="'.$gruppo['nome'].'">
    <div class="box-header clickable" id="impostazioni-'.$key.'">
        <div class="box-title">'.tr('_SEZIONE_', [
            '_SEZIONE_' => $gruppo['nome'],
        ]).'</div>
        <div class="box-tools pull-right">
            <div class="badge">'.$gruppo['numero'].'</div>
        </div>
    </div>

    <div class="box-body""></div>
</div>';
}

echo '
<script>
globals.impostazioni = {
    errors: {},
};

$("[id^=impostazioni]").click(function() {
    caricaSezione(this);
});

$("#ricerca_impostazioni").change(function (){
    let ricerca = $(this).val();
    $(".box").removeClass("hidden");

    if (ricerca) {
        $.get("'.$structure->fileurl('actions.php').'?id_module='.$id_module.'&op=ricerca&search=" + ricerca, function(data) {
            $(".box").addClass("hidden");

            let sezioni = JSON.parse(data);
            for(const sezione of sezioni){
                $(`.box[title="` + sezione + `"]`).removeClass("hidden");
            }
        });
    }
})

function caricaSezione(header) {
    let box = $(header).closest(".box");
    box.toggleClass("collapsed-box");

    // Controllo sul caricamento già effettuato
    let container = box.find(".box-body");
    if (container.html()){
        return ;
    }

    // Caricamento della sezione di impostazioni
    let sezione = box.attr("title");
    localLoading(container, true);
    return $.get("'.$structure->fileurl('sezione.php').'?id_module='.$id_module.'&sezione=" + sezione, function(data) {
        container.html(data);
        localLoading(container, false);
    });
}

function salvaImpostazione(id, valore){
    $.ajax({
        url: globals.rootdir + "/actions.php",
        cache: false,
        type: "GET",
        dataType: "JSON",
        data: {
            op: "salva",
            id_module: globals.id_module,
            id: id,
            valore: valore,
        },
        success: function(data) {
            renderMessages();

            if(!data.result) {
                globals.impostazioni.errors[id] = valore;
                $("#riprova_salvataggi").removeClass("hidden");
            }
        },
        error: function(data) {
            swal("'.tr('Errore').'", "'.tr('Errore durante il salvataggio dei dati').'", "error");
        }
    });
}

function riprovaSalvataggio() {
    const impostazioni = JSON.parse(JSON.stringify(globals.impostazioni.errors));;
    globals.impostazioni.errors = {};

    $("#riprova_salvataggi").addClass("hidden");
    for ([id, valore] of Object.entries(impostazioni)) {
        salvaImpostazione(id, valore);
    }
}
</script>';
