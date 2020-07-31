<?php

include_once __DIR__.'/../../core.php';

use Modules\DDT\DDT;
use Modules\Ordini\Ordine;

$documento_finale = DDT::find($id_record);
$dir = $documento_finale->direzione;

$id_documento = get('id_documento');
if (!empty($id_documento)) {
    $documento = Ordine::find($id_documento);

    $options = [
        'op' => 'add_ordine',
        'button' => tr('Aggiungi'),
        'serials' => true,
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
        {[ "type": "select", "label": "'.tr('Ordine').'", "name": "id_documento", "values": "query=SELECT or_ordini.id, CONCAT(IF(numero_esterno != \'\', numero_esterno, numero), \' del \', DATE_FORMAT(data, \'%d-%m-%Y\')) AS descrizione FROM or_ordini WHERE idanagrafica='.prepare($id_anagrafica).' AND idstatoordine IN (SELECT id FROM or_statiordine WHERE descrizione IN(\'Accettato\', \'Evaso\', \'Parzialmente evaso\', \'Parzialmente fatturato\')) AND idtipoordine=(SELECT id FROM or_tipiordine WHERE dir='.prepare($dir).' LIMIT 0,1) AND or_ordini.id IN (SELECT idordine FROM or_righe_ordini WHERE or_righe_ordini.idordine = or_ordini.id AND (qta - qta_evasa) > 0) ORDER BY data DESC, numero DESC" ]}
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

    $(document).ready(function() {
        loader.hide();
    });

    $("#id_documento").on("change", function() {
        loader.show();

        var id = $(this).selectData() ? $(this).selectData().id  : "";

        content.html("");
        content.load("'.$structure->fileurl($file).'?id_module='.$id_module.'&id_record='.$id_record.'&id_documento=" + id, function() {
            loader.hide();
        });
    });
</script>';
