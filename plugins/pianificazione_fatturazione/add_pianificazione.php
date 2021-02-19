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

use Modules\Contratti\Contratto;

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/../modutil.php';

$contratto = Contratto::find($id_record);

$giorni_fatturazione = [];
for ($i = 1; $i <= 31; ++$i) {
    $giorni_fatturazione[] = [
        'id' => $i,
        'text' => $i,
    ];
}

echo '
<form action="" method="post">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_module" value="'.$id_module.'">
	<input type="hidden" name="id_plugin" value="'.$id_plugin.'">
	<input type="hidden" name="id_record" value="'.$id_record.'">

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs nav-justified">
            <li class="active"><a href="#periodi" data-tab="periodi" onclick="apriTab(this)" data-toggle="tab">'.tr('Periodi').'</a></li>

            <li><a href="#div_righe" data-tab="righe" data-toggle="tab">'.tr('Righe').'</a></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="periodi">
                <br>
                <div class="row">
                    <div class="col-md-3">
                        {[ "type": "select", "label": "'.tr('Ricorrenza fatturazione').'", "name": "scadenza", "values": "list=\"\":\"Seleziona un\'opzione\", \"Mensile\":\"Mensile\", \"Bimestrale\":\"Bimestrale\", \"Trimestrale\":\"Trimestrale\", \"Quadrimestrale\":\"Quadrimestrale\", \"Semestrale\":\"Semestrale\", \"Annuale\":\"Annuale\"", "value": "Mensile", "help":"'.tr('Specificare la cadenza con cui creare la pianificazione fatturazione').'" ]}
                    </div>
                    <div class="col-md-3">
                        {[ "type": "select", "label": "'.tr('Giorno di fatturazione').'", "name": "cadenza_fatturazione", "values": "list=\"\":\"Seleziona un\'opzione\", \"Inizio\":\"Inizio mese\", \"Fine\":\"Fine mese\", \"Giorno\":\"Giorno fisso\" ", "value": "Inizio", "help":"'.tr('Specificare per la pianificazione fatturazione se si desidera creare le fatture a inizio mese o alla fine. Se non specificata alcuna opzione saranno create di default a fine mese.').'" ]}
                    </div>
                    <div class="col-md-3" id="div_giorno_fisso" hidden>
                        {[ "type": "select", "label": "'.tr('Giorno fisso fatturazione').'", "name": "giorno_fisso", "values": '.json_encode($giorni_fatturazione).', "value": "1", "help":"'.tr('Selezionare il giorno fisso di fatturazione.').'" ]}
                    </div>
                    <div class="col-md-3">
                        {[ "type": "date", "label": "'.tr('Data inizio fatturazione').'", "name": "data_inizio", "value": "'.$contratto->data_accettazione.'", "help":"'.tr('Data da cui far partire la prima fattura del contratto.').'" ]}
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <h4 id="total_check">Rate: 0</h4>
                    </div>
                </div>
                <br>
                <div id="cadenza">
                    <script>$("#cadenza").load(</script>
                </div>

                <br>

                <div class="row">
                    <div class="col-md-12">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-primary" onclick="selezionaTutto()">
                                '.tr('Tutti').'
                            </button>

                            <button type="button" class="btn btn-sm btn-danger" onclick="deselezionaTutto()">
                            <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane" id="div_righe">';

$iva_righe = $contratto->getRighe()->groupBy('idiva');

/*
foreach ($iva_righe as $id_iva => $righe) {
    $iva = $righe->first()->aliquota;
    $descrizione = $righe->first()->descrizione;
    $righe = $righe->toArray();
*/

$righe = $contratto->getRighe();

echo '
<div class="alert alert-info">
    <p>'.tr('Puoi utilizzare le seguenti variabili nella descrizione delle righe').':</p>'.variables()['list'].'
</div>';

foreach ($righe as $riga) {
    $id_iva = $riga->id_iva;
    $descrizione = $riga->descrizione."\n{periodo}";

    $options = [
        'id' => $riga->id,
        'totale_imponibile' => $riga->totale_imponibile,
        'iva' => $riga->iva,
        'totale' => $riga->totale,
        'qta' => $riga->qta,
    ];
    $options = json_encode($options);

    echo '
                <!--h5>'.tr('Informazioni generali sulle righe con IVA: _IVA_', [
                    '_IVA_' => $riga->iva->descrizione,
                ]).'</h5-->

                <div class="row">
                    <div class="col-md-9">
                        {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione['.$riga->id.']", "value": "'.$descrizione.'" ]}

                        {[ "type": "number", "label": "'.tr('Q.tà per fattura').'", "class":"qta_fattura", "name": "qta['.$riga->id.']", "required": 1, "value": "1", "decimals": "qta", "min-value": "1", "icon-after":"'.tr('Su _TOT_', [
                            '_TOT_' => Translator::numberToLocale(($riga->qta - $riga->qta_evasa)),
                        ]).'", "options":"'.str_replace('"', '\"', $options).'" ]}
                    </div>
                    <div class="col-md-3" id="totali_'.$riga->id.'">
                    </div>
                </div>
                <hr>';
}

echo '
            <div class="row">
                <div class="col-md-offset-9 col-md-3" id="div_totale">
                </div>
            </div>';

echo '
            </div>
        </div>
    </div>

    <div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-chevron-right"></i> '.tr('Procedi').'</button>
		</div>
	</div>
</form>';

echo '
<script>$(document).ready(init)</script>

<script>
    $(document).ready(function(){
        caricaCadenza();
        get_prezzi();
    });

    $("#scadenza").change(function(){
        caricaCadenza();
    });

    $("#data_inizio").focusout(function(){
        caricaCadenza();
    });

    function caricaCadenza() {
        let container = $("#cadenza");

        localLoading(container, true);
        return $.get("'.$structure->fileurl('ajax_cadenza.php').'?id_module='.$id_module.'&id_record='.$id_record.'&scadenza="+$("#scadenza").val()+"&data_inizio="+$("#data_inizio").val(), function(data) {
            container.html(data);
            localLoading(container, false);
        });
    }


    $("input:checkbox").click(function(){
        var check = 0;
        $("#periodi input").each(function (){
            if( $(this).is(":checked") ){
                check = check + 1;
            }
        });

        $("#total_check").html("Rate: " + check).trigger("change");
    });

    function selezionaTutto(){
        var check = 0;
        $("#periodi input").each(function (){
            $("input:checkbox").prop("checked",true);
            if( $("input:checkbox").is(":checked") ){
                check = check + 1;
            }
        });

        $("#total_check").html("Rate: " + check).trigger("change");
    }

    function deselezionaTutto(){
        var check = 0;
        $("#periodi input").each(function (){
            $("input:checkbox").prop("checked",false);
            if( $("input:checkbox").is(":checked") ){
                check = check + 1;
            }
        });

        $("#total_check").html("Rate: " + check).trigger("change");
    }

    $(".qta_fattura").change(function(){
        get_prezzi();
    });

    function get_prezzi(){

        $(".qta_fattura").each(function(){
            var qta = parseFloat($(this).val().replace(",",".")).toFixed(2);
            var riga = JSON.parse($(this).attr("options"));

            var imponibile_riga = (riga.totale_imponibile/riga.qta)*qta;
            imponibile_riga = imponibile_riga.toLocaleString()+" &euro;";

            var iva_riga = (riga.iva/riga.qta)*qta;
            iva_riga = iva_riga.toLocaleString()+" &euro;";

            var totale_riga = (riga.totale/riga.qta)*qta;
            totale_riga = totale_riga.toLocaleString()+" &euro;";

            $("#totali_"+riga.id).html("<p><b>Imponibile</b>: "+imponibile_riga+"</p>\
            <p><b>IVA</b>: "+iva_riga+"</p>\
            <p><b>Totale</b>: "+totale_riga+"</p>");

        });
    }

    $("#cadenza_fatturazione").change(function(){
        if( $(this).val()=="Giorno" ){
            $("#div_giorno_fisso").show();
        }else{
            $("#giorno_fisso").selectReset();
            $("#div_giorno_fisso").hide();
        }
    })

</script>';
