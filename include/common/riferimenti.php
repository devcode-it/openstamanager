<?php

include_once __DIR__.'/../../core.php';

use Modules\DDT\DDT;
use Modules\Ordini\Ordine;

// Generazione della tabella per l'aggiunta dei riferimenti
$id_documento = filter('id_documento');
$tipo_documento = filter('tipo_documento');
if (!empty($tipo_documento)) {
    $documento = $tipo_documento::find($id_documento);

    echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <tr>
        <th>'.tr('Descrizione').'</th>
        <th>'.tr('Q.tà').' <i title="'.tr('da evadere').' / '.tr('totale').'" class="tip fa fa-question-circle-o"></i></th>
        <th class="text-center">#</th>
    </tr>

    <tbody>';

    $righe = $documento->getRighe();
    foreach ($righe as $riga) {
        echo '
        <tr>
            <td>'.$riga->descrizione.'</td>
            <td>'.numberFormat($riga->qta_rimanente, 'qta').' / '.numberFormat($riga->qta, 'qta').'</td>
            <td class="text-center">
                <button type="button" class="btn btn-info btn-xs" onclick="selezionaRiga(this, \''.$tipo_documento.'\',\''.$id_documento.'\', \''.addslashes(get_class($riga)).'\', \''.$riga->id.'\')">
                    <i class="fa fa-check"></i>
                </button>
            </td>
        </tr>';
    }

    echo '
    </tbody>
</table>';

    return;
}

// Informazioni generali sulla riga
$riga_id = filter('riga_id');
$riga_type = filter('riga_type');

$riga = $riga_type::find($riga_id);

echo '
<p>'.tr('Informazioni per i riferimenti di: _DESC_', [
    '_DESC_' => $riga->descrizione,
]).'</p>';

$riferimenti = $riga->referenceTargets;
if (!$riferimenti->isEmpty()) {
    echo '
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>'.tr('Riferimento').'</th>
            <th>#</th>
        </tr>
    </thead>

    <tbody>';

    foreach ($riferimenti as $riferimento) {
        echo '
        <tr>
            <td>'.reference($riferimento->target->parent).'</td>
            <td class="text-center">
                <button type="button" class="btn btn-xs btn-danger">
                    <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr>';
    }

    echo '
    </tbody>
</table>';
} else {
    echo '
<p>'.tr('Nessun riferimento presente').'.</p>';
}

$documenti_disponibili = collect();

// Indivuduazione DDT disponibili
$ddt = DDT::whereHas('stato', function ($query) {
    $query->where('descrizione', '=', 'Bozza');
})->get();
foreach ($ddt as $elemento) {
    $documenti_disponibili->push([
        'id' => get_class($elemento).'|'.$elemento->id,
        'text' => $elemento->getReference(),
        'optgroup' => 'DDT',
    ]);
}

// Individuazione ordini disponibili
$ordini = Ordine::whereHas('stato', function ($query) {
    $query->where('descrizione', '=', 'Bozza');
})->get();
foreach ($ordini as $elemento) {
    $documenti_disponibili->push([
        'id' => get_class($elemento).'|'.$elemento->id,
        'text' => $elemento->getReference(),
        'optgroup' => 'Ordini',
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
    var content = $("#righe_documento");
    var loader = $("#box-loading");

    $(document).ready(function(){
        loader.hide();
    });

    $("#documento_riferimento").on("change", function(){
        var value = $(this).val();
        if (value) {
            var pieces = value.split("|");

            var type = pieces[0];
            var id = pieces[1];
            caricaRighe(type, id);
        }
    });

    function caricaRighe(tipo_documento, id_documento){
        loader.show();

        content.html("");
        $.ajax({
            url: globals.rootdir + "/actions.php",
            cache: false,
            type: "GET",
            data: {
                id_module: '.$id_module.',
                id_record: '.$id_record.',
                op: "visualizza_riferimenti_documento",
                id_documento: id_documento,
                tipo_documento: tipo_documento,
            },
            success: function(data) {
                loader.hide();

                content.html(data)
            }
        });
    }
</script>';
