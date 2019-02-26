<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

$dir = ($module['name'] == 'Fatture di vendita') ? 'entrata' : 'uscita';

if (get('op')) {
    $options = [
        'op' => 'add_contratto',
        'id_importazione' => 'id_contratto',
        'final_module' => $module['name'],
        'original_module' => 'Contratti',
        'sql' => [
            'table' => 'co_contratti',
            'rows' => 'co_righe_contratti',
            'id_rows' => 'idcontratto',
        ],
        'serials' => false,
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

echo '
<div class="row">
    <div class="col-md-12">
        {[ "type": "select", "label": "'.tr('Contratto').'", "name": "id_documento", "ajax-source": "contratti" ]}
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
