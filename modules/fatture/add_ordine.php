<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

$dir = ($module['name'] == 'Fatture di vendita') ? 'entrata' : 'uscita';

if (get('op')) {
    $options = [
        'op' => 'add_ordine',
        'id_importazione' => 'id_ordine',
        'final_module' => $module['name'],
        'original_module' => $module['name'] == 'Fatture di vendita' ? 'Ordini cliente' : 'Ordini fornitore',
        'sql' => [
            'table' => 'or_ordini',
            'rows' => 'or_righe_ordini',
            'id_rows' => 'idordine',
        ],
        'serials' => [
            'id_riga' => 'id_riga_ddt',
            'condition' => '(id_riga_documento IS NOT NULL)',
        ],
        'button' => tr('Aggiungi'),
        'dir' => $dir,
    ];

    $result = [
        'id_record' => $id_record,
        'id_documento' => get('iddocumento'),
    ];

    echo App::load('importa.php', $result, $options, true);

    return;
}

$info = $dbo->fetchOne('SELECT * FROM co_documenti WHERE id='.prepare($id_record));
$idanagrafica = $info['idanagrafica'];

echo '
<div class="row">
    <div class="col-md-12">
        {[ "type": "select", "label": "'.tr('Ordine').'", "name": "id_documento", "values": "query=SELECT or_ordini.id, CONCAT(IF(numero_esterno != \'\', numero_esterno, numero), \' del \', DATE_FORMAT(data, \'%d-%m-%Y\')) AS descrizione FROM or_ordini WHERE idanagrafica='.prepare($idanagrafica).' AND id_stato IN (SELECT id FROM or_statiordine WHERE descrizione IN(\'Bozza\', \'Evaso\', \'Parzialmente evaso\', \'Parzialmente fatturato\')) AND idtipoordine=(SELECT id FROM or_tipiordine WHERE dir='.prepare($dir).' LIMIT 0,1) AND or_ordini.id IN (SELECT idordine FROM or_righe_ordini WHERE or_righe_ordini.idordine = or_ordini.id AND (qta - qta_evasa) > 0) ORDER BY data DESC, numero DESC" ]}
    </div>
</div>

<div class="box" id="info-box">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Informazioni di importazione').'</h3>
    </div>
    <div class="box-body" id="righe_documento">
    </div>
</div>

<div class="alert alert-info" id="box-loading">
    <i class="fa fa-spinner fa-spin"></i> '.tr('Caricamento in corso').'...
</div>';

$file = basename(__FILE__);
echo '
<script src="'.$rootdir.'/lib/init.js"></script>

<script>
    var box = $("#info-box");
    var content = $("#righe_documento");
    var loader = $("#box-loading");

    $(document).ready(function(){
        box.hide();
        loader.hide();
    })

    $("#id_documento").on("change", function(){
        loader.show();
        box.hide();

        var id = $(this).selectData() ? $(this).selectData().id  : "";

        content.html("<i>'.tr('Caricamento in corso').'...</i>");
        content.load("'.$structure->fileurl($file).'?id_module='.$id_module.'&id_record=" + id + "&documento=fattura&op=add_ordine&iddocumento='.$id_record.'", function() {
            if(content.html() != ""){
                box.show();
            }

            loader.hide();
        });


    });
</script>';
