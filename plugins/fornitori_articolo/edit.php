<?php

use Plugins\FornitoriArticolo\Dettaglio;

include_once __DIR__.'/../../core.php';

echo '
<p>'.tr("In questa sezione è possibile definire le caratteristiche di base dell'articolo in relazione fornitore di origine, come codice e prezzo di acquisto predefinito").'. '.tr("Queste informazioni saranno utilizzate in automatico per la compilazione dell'articolo al momento dell'inserimento in un documento di acquisto relativo al fornitore indicato, sovrascrivendo le impostazioni predefinite della sezione Acquisto per l'articolo").'.</p>
<p>'.tr("Ogni fornitore, tra cui si evidenzia quello predefinito per l'articolo, può essere descritto una sola volta con le informazioni aggiuntive").'</p>

<div class="box">
    <div class="box-header">
        <h3 class="box-title">'.tr('Nuovo fornitore').'</h3>
    </div>

    <div class="box-body">
        <div class="row">
            <div class="col-md-9">
                {[ "type": "select", "label": "'.tr('Fornitore').'", "name": "id_fornitore_informazioni", "required": 1, "ajax-source": "fornitori", "value": "" ]}
            </div>

            <div class="col-md-3">
                <button type="button" class="btn btn-primary btn-block" style="margin-top:25px;" onclick="add_fornitore()">
                    <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                </button>
            </div>
        </div>
    </div>
</div>';

$fornitori = Dettaglio::where('id_articolo', $id_record)->get();
if (!$fornitori->isEmpty()) {
echo '
<h4>'.tr('Elenco fornitori').'</h4>
<table class="table table-striped table-condensed table-bordered">
    <thead>
        <tr>
            <th>'.tr('Ragione sociale').'</th>
            <th class="text-center">'.tr('Q.tà minima ordinabile').'</th>
            <th class="text-center">'.tr('Tempi di consegna').'</th>
            <th class="text-center">'.tr('Prezzo acquisto').'</th>
            <th class="text-center" width="70"></th>
        </tr>
    </thead>

    <tbody>';

    foreach ($fornitori as $fornitore) {
        $anagrafica = $fornitore->anagrafica;

        $color = '';
        if ($anagrafica->id ['predefinito'] == $articolo->id_fornitore) {
            $color = '#b5f4a9';
        }

        echo '
            <tr style="background-color:'.$color.'">
            <td>
                '.Modules::link('Anagrafiche', $anagrafica->id, $anagrafica->ragione_sociale).'
                <br>
                <small>'.tr('Codice: _CODE_', [
                    '_CODE_' => $fornitore['codice_fornitore'],
                ]).'</small>
            </td>

            <td class="text-right">
                '.Translator::numberToLocale($fornitore['qta_minima']).'
            </td>

            <td class="text-right">
                '.tr('_NUM_ giorni', [
                    '_NUM_' => numberFormat($fornitore['giorni_consegna']),
                ]).'
            </td>

            <td class="text-right">
                <span>'.moneyFormat($fornitore['prezzo_acquisto']).'</span>
            </td>

            <td class="text-center">
                <a class="btn btn-secondary btn-xs btn-warning" onclick="edit_fornitore('.$fornitore['id'].', '.$anagrafica->id.')">
                    <i class="fa fa-edit"></i>
                </a>

                <a class="btn btn-secondary btn-xs btn-danger ask" data-op="delete_fornitore" data-id_riga="'.$fornitore['id'].'" data-id_plugin="'.$id_plugin.'" data-backto="record-edit">
                    <i class="fa fa-trash-o"></i>
                </a>
            </td>
        </tr>';
    }

    echo '
    </tbody>
</table>';
} else {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr('Nessuna informazione disponibile').'...
</div>';
}

echo '

<script>
function edit_fornitore(id_riga, id_anagrafica) {
    openModal("Modifica dati fornitore", "'.$structure->fileurl('edit_fornitore.php').'?id_plugin='.$id_plugin.'&id_module='.$id_module.'&id_parent='.$id_record.'&id_articolo='.$id_record.'&id_riga=" + id_riga + "&id_anagrafica=" + id_anagrafica);
}

function add_fornitore() {
    var id_fornitore = $("#id_fornitore_informazioni").selectData().id;
    if (id_fornitore){
        edit_fornitore(null, id_fornitore);
    } else {
        swal("'.tr('Errore').'", "'.tr('Nessun fornitore selezionato').'", "error");
    }
}
</script>';
