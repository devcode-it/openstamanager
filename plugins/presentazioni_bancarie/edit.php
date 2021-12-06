<?php

include_once __DIR__.'/init.php';

if (!empty($records)) {
    include $structure->filepath('generate.php');

    return;
}

echo '
<div class="row">
    <div class="col-md-3">
        {[ "type": "checkbox", "label": "'.tr('Esporta già pagati').'", "name": "pagati", "help": "'.tr('Seleziona per esportare le scadenze già pagate tra quelle selezionate').'" ]}
    </div>

    <div class="col-md-3">
        {[ "type": "checkbox", "label": "'.tr('Esporta già processati').'", "name": "processati", "help": "'.tr('Seleziona per esportare nuovamente le scadenze esportate in precedenze tra quelle selezionate').'" ]}
    </div>
</div>

<div class="row">
    <div class="col-md-12 text-right">
        <button type="button" class="btn btn-warning" onclick="esporta(this)">
            <i class="fa fa-download"></i> '.tr('Esporta').'
        </button>
    </div>
</div>

<script>
function getRecords() {
    let table = $(".main-records[id^=main]").first();
    let datatable = getTable("#" + table.attr("id"));

    return datatable.getSelectedRows();
}

function esporta(button) {
    let records = getRecords();
    if (!records.length) {
        swal("'.tr('Errore').'", "'.tr('Selezione assente!').'", "error");
        return;
    }

    let pagati = input("pagati").get();
    let processati = input("processati").get();

    redirect("'.base_path().'/editor.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&records=" + records + "&pagati=" + pagati + "&processati=" + processati);
}
</script>';
