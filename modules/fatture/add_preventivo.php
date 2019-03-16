<?php

$module = Modules::get($id_module);

if (get('op')) {
    $options = [
        'op' => 'add_preventivo',
        'id_importazione' => 'id_preventivo',
        'final_module' => $module['name'],
        'original_module' => 'Preventivi',
        'sql' => [
            'table' => 'co_preventivi',
            'rows' => 'co_righe_preventivi',
            'id_rows' => 'idpreventivo',
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
        {[ "type": "select", "label": "'.tr('Preventivo').'", "name": "id_documento", "ajax-source": "preventivi" ]}
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
<script src="'.$rootdir.'/assets/js/init.min.js"></script>
    
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
