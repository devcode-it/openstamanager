<?php

use Modules\Banche\Banca;
use Plugins\PresentazioniBancarie\Gestore;

include_once __DIR__.'/init.php';

if (!empty($records)) {
    include $structure->filepath('generate.php');

    return;
} else {
    $banca_azienda = Banca::where('id_anagrafica', Gestore::getAzienda()->id)
    ->where('predefined', 1)
    ->first();

    try {
        if (empty($banca_azienda)) {
            echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i>
    '.tr("La banca dell'azienda non è definita o non ha impostati i campi Codice IBAN e BIC").'.
    '.Modules::link('Banche', null, tr('Imposta'), null, null).'
</div>';
        }
    } catch (Exception $e) {
    }
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
        <button type="button" class="btn btn-primary '.(!empty($banca_azienda) ? '' : 'disabled').'" onclick="esporta(this)">
            <i class="fa fa-arrow-right"></i> '.tr('Continua').'...
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
        swal("'.tr('Errore').'", "'.tr('Selezionare almeno una scadenza.').'", "error");
        return;
    }

    let pagati = input("pagati").get();
    let processati = input("processati").get();

    redirect("'.base_path().'/editor.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&records=" + records + "&pagati=" + pagati + "&processati=" + processati);
}
</script>';
