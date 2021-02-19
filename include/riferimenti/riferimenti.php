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

include_once __DIR__.'/../../core.php';

use Modules\DDT\DDT;
use Modules\Ordini\Ordine;

// Informazioni generali sulla riga
$source_type = filter('riga_type');
$source_id = filter('riga_id');
if (empty($source_type) || empty($source_id)) {
    return;
}

$source = $source_type::find($source_id);

echo '
<p>'.tr('Informazioni per i riferimenti di: _DESC_', [
    '_DESC_' => $source->descrizione,
]).'</p>

<div id="righe_riferimenti">';

include_once __DIR__.'/righe_riferimenti.php';

echo '
</div>

<div class="alert alert-info" id="box-loading-riferimenti">
    <i class="fa fa-spinner fa-spin"></i> '.tr('Caricamento in corso').'...
</div>';

$documenti_disponibili = collect();
$direzione_richiesta = $source->getDocument()->direzione == 'entrata' ? 'uscita' : 'entrata';

// Individuazione DDT disponibili
$ddt = DDT::whereHas('stato', function ($query) {
    $query->where('descrizione', '!=', 'Bozza');
})->whereHas('tipo', function ($query) use ($direzione_richiesta) {
    $query->where('dir', '=', $direzione_richiesta);
})->get();
foreach ($ddt as $elemento) {
    $documenti_disponibili->push([
        'id' => get_class($elemento).'|'.$elemento->id,
        'text' => $elemento->getReference(1),
        'optgroup' => tr('Ddt in ').$source->getDocument()->direzione,
    ]);
}

// Individuazione ordini disponibili
$tipo_ordini = $direzione_richiesta == 'entrata' ? 'cliente' : 'fornitore';
$ordini = Ordine::whereHas('stato', function ($query) {
    $query->where('descrizione', '!=', 'Bozza');
})->whereHas('tipo', function ($query) use ($direzione_richiesta) {
    $query->where('dir', '=', $direzione_richiesta);
})->get();
foreach ($ordini as $elemento) {
    $documenti_disponibili->push([
        'id' => get_class($elemento).'|'.$elemento->id,
        'text' => $elemento->getReference(1),
        'optgroup' => tr('Ordini ').$tipo_ordini,
    ]);
}

echo '
<div class="box">
    <div class="box-header">
        <h3 class="box-title">'.tr('Nuovo riferimento').'</h3>
    </div>

    <div class="box-body">
        <div class="row">
            <div class="col-md-12">
                {[ "type": "select", "label": "'.tr('Documento').'", "name": "documento_riferimento", "required": 1, "values": '.$documenti_disponibili->toJson().' ]}
            </div>
        </div>

        <div id="righe_documento"></div>

        <div class="alert alert-info" id="box-loading">
            <i class="fa fa-spinner fa-spin"></i> '.tr('Caricamento in corso').'...
        </div>
    </div>
</div>';

$file = basename(__FILE__);
echo '
<script>$(document).ready(init)</script>

<script>
    $(document).ready(function(){
        $("#box-loading").hide();
        $("#box-loading-riferimenti").hide();
    });

    var riferimenti = JSON.parse(\''.json_encode($elenco_riferimenti).'\');
    var source_type = "'.addslashes($source_type).'";
    var source_id = "'.$source_id.'";

    $("#documento_riferimento").on("change", function(){
        let value = $(this).val();
        if (value) {
            let pieces = value.split("|");

            let type = pieces[0];
            let id = pieces[1];
            caricaRighe(type, id);
        }
    });

    function caricaRiferimenti() {
        let loader = $("#box-loading-riferimenti");
        let content = $("#righe_riferimenti");

        loader.show();

        content.html("");
        $.ajax({
            url: globals.rootdir + "/actions.php",
            cache: false,
            type: "GET",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "visualizza_righe_riferimenti",
                source_type: source_type,
                source_id: source_id,
            },
            success: function(data) {
                loader.hide();

                content.html(data);
                $("#documento_riferimento").trigger("change");
            }
        });
    }

    function caricaRighe(tipo_documento, id_documento){
        let content = $("#righe_documento");
        let loader = $("#box-loading");

        loader.show();
        content.html("");
        $.ajax({
            url: globals.rootdir + "/actions.php",
            cache: false,
            type: "GET",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "visualizza_righe_documento",
                source_type: source_type,
                source_id: source_id,
                id_documento: id_documento,
                tipo_documento: tipo_documento,
                riferimenti: riferimenti,
            },
            success: function(data) {
                loader.hide();

                content.html(data);
            }
        });
    }

    function salvaRiferimento(btn, source_type, source_id) {
        $("#main_loading").show();

        let row = $(btn).closest("tr");
        let target_type = row.data("type");
        let target_id = row.data("id");

        $.ajax({
            url: globals.rootdir + "/actions.php",
            cache: false,
            type: "POST",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "salva_riferimento_riga",
                source_type: source_type,
                source_id: source_id,
                target_type: target_type,
                target_id: target_id,
            },
            success: function(data) {
                $("#main_loading").fadeOut();

                // Aggiunta del riferimento in memoria
                let riferimento_locale = target_type + "|" + target_id;
                riferimenti.push(riferimento_locale);

                $(btn).removeClass("btn-info").addClass("btn-success");

                caricaRiferimenti();
            }
        });
    }

    function rimuoviRiferimento(btn, source_type, source_id, idriferimento) {
        $("#main_loading").show();

        let row = $(btn).closest("tr");
        let target_type = row.data("type");
        let target_id = row.data("id");

        $.ajax({
            url: globals.rootdir + "/actions.php",
            cache: false,
            type: "POST",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "rimuovi_riferimento_riga",
                source_type: source_type,
                source_id: source_id,
                target_type: target_type,
                target_id: target_id,
                idriferimento: idriferimento,
            },
            success: function(data) {
                $("#main_loading").fadeOut();

                // Rimozione del riferimento dalla memoria
                let riferimento_locale = target_type + "|" + target_id;
                let index = riferimenti.indexOf(riferimento_locale);
                if (index > -1) {
                    riferimenti.splice(index, 1);
                }

                caricaRiferimenti();
            }
        });
    }
</script>';
