<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

$dir = ($module['name'] == 'Fatture di vendita') ? 'entrata' : 'uscita';

if (get('op')) {
    $options = [
        'op' => 'add_ddt',
        'id_importazione' => 'id_ddt',
        'final_module' => $module['name'],
        'original_module' => $module['name'] == 'Fatture di vendita' ? 'Ddt di vendita' : 'Ddt di acquisto',
        'sql' => [
            'table' => 'dt_ddt',
            'rows' => 'dt_righe_ddt',
            'id_rows' => 'idddt',
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
            {[ "type": "select", "label": "'.tr('Ddt').'", "name": "id_documento", "values": "query=SELECT dt_ddt.id, CONCAT(\'DDT num. \', IF(numero_esterno != \'\', numero_esterno, numero), \' del \', DATE_FORMAT(data, \'%d-%m-%Y\')) AS descrizione FROM dt_ddt WHERE idanagrafica='.prepare($idanagrafica).' AND id_stato IN (SELECT id FROM dt_statiddt WHERE descrizione IN(\'Bozza\', \'Evaso\', \'Parzialmente evaso\', \'Parzialmente fatturato\')) AND id_tipo_ddt = (SELECT id FROM dt_tipiddt WHERE dir='.prepare($dir).') AND dt_ddt.id IN (SELECT idddt FROM dt_righe_ddt WHERE dt_righe_ddt.idddt = dt_ddt.id AND (qta - qta_evasa) > 0) ORDER BY data DESC, numero DESC" ]}
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
