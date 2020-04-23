<?php

include_once __DIR__.'/../../core.php';

use Modules\DDT\DDT;
use Modules\Fatture\Fattura;

$documento_finale = Fattura::find($id_record);
$dir = $documento_finale->direzione;

$id_documento = get('id_documento');
if (!empty($id_documento)) {
    $documento = DDT::find($id_documento);

    $options = [
        'op' => 'add_documento',
        'type' => 'ddt',
        'serials' => true,
        'button' => tr('Aggiungi'),
        'documento' => $documento,
        'documento_finale' => $documento_finale,
    ];

    echo App::load('importa.php', [], $options, true);

    return;
}

$id_anagrafica = $documento_finale->idanagrafica;

echo '
<div class="row">
    <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Ddt').'", "name": "id_documento", "values": "query=SELECT dt_ddt.id, CONCAT(\'DDT num. \', IF(numero_esterno != \'\', numero_esterno, numero), \' del \', DATE_FORMAT(data, \'%d-%m-%Y\'), \' [\', (SELECT descrizione FROM dt_statiddt WHERE id = idstatoddt)  , \']\') AS descrizione FROM dt_ddt WHERE idanagrafica='.prepare($id_anagrafica).' AND idstatoddt IN (SELECT id FROM dt_statiddt WHERE descrizione IN(\'Evaso\', \'Parzialmente evaso\', \'Parzialmente fatturato\')) AND idtipoddt=(SELECT id FROM dt_tipiddt WHERE dir='.prepare($dir).') AND dt_ddt.id IN (SELECT idddt FROM dt_righe_ddt WHERE dt_righe_ddt.idddt = dt_ddt.id AND (qta - qta_evasa) > 0) ORDER BY data DESC, numero DESC" ]}
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
    
    $(document).ready(function(){
        loader.hide();
    });
    
    $("#id_documento").on("change", function(){
        loader.show();

        var id = $(this).selectData() ? $(this).selectData().id  : "";
    
        content.html("");
        content.load("'.$structure->fileurl($file).'?id_module='.$id_module.'&id_record='.$id_record.'&id_documento=" + id, function() {
            loader.hide();
        });
    });
</script>';
