<?php

use Modules\DDT\DDT;
use Modules\Ordini\Ordine;

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/init.php';

$direzione = 'uscita';
$id_anagrafica = $anagrafica->id;
$id_riga = get('id_riga');
$qta = get('qta');

$id_documento = get('id_documento');
$tipo_documento = get('tipo_documento');
if (!empty($tipo_documento)) {
    if ($tipo_documento == 'ordine') {
        $documento = Ordine::find($id_documento);
        $righe_utilizzate = get('righe_ordini');
    } else {
        $documento = DDT::find($id_documento);
        $righe_utilizzate = get('righe_ddt');
    }

    echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <tr>
        <th>'.tr('Descrizione').'</th>
        <th width="120">'.tr('Q.tà').' <i title="'.tr('da evadere').' / '.tr('totale').'" class="tip fa fa-question-circle-o"></i></th>
        <th class="text-center" width="60">#</th>
    </tr>

    <tbody>';

    $id_riferimento = get('id_riferimento');
    $righe = $documento->getRighe();
    foreach ($righe as $riga) {
        $qta_rimanente = $riga->qta_rimanente - $righe_utilizzate[$riga->id];

        echo '
        <tr '.($id_riferimento == $riga->id ? 'class="success"' : '').'>
            <td>'.$riga->descrizione.'</td>
            <td>'.numberFormat($qta_rimanente, 'qta').' / '.numberFormat($riga->qta, 'qta').'</td>
            <td class="text-center">';

        if ($qta_rimanente >= $qta) {
            echo '
                <button type="button" class="btn btn-info btn-xs" onclick="selezionaRiga(\''.$tipo_documento.'\',\''.$id_documento.'\', \''.addslashes(get_class($riga)).'\', \''.$riga->id.'\', \'Rif. '.$tipo_documento.' num. '.$documento->numero.'\')">
                    <i class="fa fa-check"></i>
                </button>';
        }

        echo '
            </td>
        </tr>';
    }

    echo '
    </tbody>
</table>';

    return;
}

echo '
<div class="row">
    <div class="col-md-6">
        {[ "type": "select", "label": "'.tr('Ordine').'", "name": "id_ordine", "values": "query=SELECT or_ordini.id, CONCAT(\'Ordine num. \', IF(numero_esterno != \'\', numero_esterno, numero), \' del \', DATE_FORMAT(data, \'%d-%m-%Y\'), \' [\', (SELECT descrizione FROM or_statiordine WHERE id = idstatoordine)  , \']\') AS descrizione FROM or_ordini WHERE idanagrafica = '.prepare($id_anagrafica).' AND idstatoordine IN (SELECT id FROM or_statiordine WHERE descrizione IN(\'Bozza\', \'Accettato\', \'Parzialmente evaso\')) AND idtipoordine IN (SELECT id FROM or_tipiordine WHERE dir='.prepare($direzione).') ORDER BY data DESC, numero DESC", "value": "" ]}
    </div>

    <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Ddt').'", "name": "id_ddt", "values": "query=SELECT dt_ddt.id, CONCAT(\'DDT num. \', IF(numero_esterno != \'\', numero_esterno, numero), \' del \', DATE_FORMAT(data, \'%d-%m-%Y\'), \' [\', (SELECT descrizione FROM dt_statiddt WHERE id = idstatoddt)  , \']\') AS descrizione FROM dt_ddt WHERE idanagrafica = '.prepare($id_anagrafica).' AND idstatoddt IN (SELECT id FROM dt_statiddt WHERE descrizione IN(\'Bozza\', \'Parzialmente evaso\')) AND idtipoddt IN (SELECT id FROM dt_tipiddt WHERE dir='.prepare($direzione).') ORDER BY data DESC, numero DESC" ]}
    </div>
</div>

<div id="righe_documento">

</div>

<div class="alert alert-info" id="box-loading">
    <i class="fa fa-spinner fa-spin"></i> '.tr('Caricamento in corso').'...
</div>';

$file = basename(__FILE__);
echo '
<script>$(document).ready(init)</script>

<script>
    var content = $("#righe_documento");
    var loader = $("#box-loading");

    var id_riga_riferimento = "'.get('id_riga_riferimento').'";

    $(document).ready(function(){
        loader.hide();';

$tipo_riferimento = get('tipo_riferimento');
if (!empty($tipo_riferimento)) {
    echo '
        $("#id_'.$tipo_riferimento.'").val('.$id_documento.').trigger("change");';
}

echo '
    });

    $("#id_ddt").on("change", function(){
        var value = $(this).val();
        if (value) {
            caricaRighe("ddt", value);
            $("#id_ordine").selectClear();
        }
    });

    $("#id_ordine").on("change", function(){
        var value = $(this).val();
        if (value) {
            caricaRighe("ordine", value);
            $("#id_ddt").selectClear();
        }
    });

    function caricaRighe(tipo_documento, id_documento){
        loader.show();

        var righe_ordini = {};
        var righe_ddt = {};
        $("[id^=tipo_riferimento_]").each(function(index, item) {
            var tipo = $(item).val();
            var c = $(item).closest("tr");

            var qta = parseFloat(c.find("[id^=qta_riferimento_]").val());
            var id_riga = c.find("[id^=id_riga_riferimento_]").val();
            if (tipo == "ordine") {
                righe_ordini[id_riga] = righe_ordini[id_riga] ? righe_ordini[id_riga] : 0;
                righe_ordini[id_riga] += qta;
            } else if (tipo == "ddt") {
                righe_ddt[id_riga] = righe_ddt[id_riga] ? righe_ddt[id_riga] : 0;
                righe_ddt[id_riga] += qta;
            }
        });

        content.html("");
        $.ajax({
            url: "'.$structure->fileurl($file).'",
            cache: false,
            type: "GET",
            data: {
                id_module: '.$id_module.',
                id_record: '.$id_record.',
                qta: '.$qta.',
                id_riferimento: id_riga_riferimento,
                id_documento: id_documento,
                tipo_documento: tipo_documento,
                righe_ddt: righe_ddt,
                righe_ordini: righe_ordini,
            },
            success: function(data) {
                loader.hide();

                content.html(data)
            }
        });
        id_riga_riferimento = "";
    }

    function selezionaRiga(tipo_documento, id_documento, tipo_riga_riferimento, id_riga_riferimento, testo) {
        impostaRiferimento("'.$id_riga.'", tipo_documento, id_documento, tipo_riga_riferimento, id_riga_riferimento, testo);
    }
</script>';
