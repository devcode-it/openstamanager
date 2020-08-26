<?php

use Plugins\DettagliArticolo\DettaglioFornitore;

include_once __DIR__.'/../../core.php';

echo '
<p>'.tr("In questa sezione è possibile definire dei dettagli aggiuntivi per l'articolo in relazione ad una specifica anagrafica del gestionale").'.</p>
<p>'.tr("Per i Clienti è possibile definire un prezzo personalizzato per la vendita dell'articolo, fisso oppure in relazione a una specifica quantità").'. '.tr("Per i Fornitori sono disponibili maggiori informazioni relative a codice, descrizione e quantità minime richieste per l'acquisto").'.</p>
<p>'.tr("Queste informazioni sono integrate con il resto del gestionale per garantire una maggiore flessibilità all'utente finale").'</p>

<div class="nav-tabs-custom">
    <ul class="nav-tabs-li nav nav-tabs nav-justified">
        <li class="active"><a href="#tab_'.$id_plugin.'" onclick="apriTab(this)" data-tab="clienti"  id="clienti-tab">'.tr('Clienti').'</a></li>

        <li><a href="#tab_'.$id_plugin.'" onclick="apriTab(this)" data-tab="fornitori">'.tr('Fornitori').'</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane active" id="clienti">
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">'.tr('Aggiungi informazioni per cliente').'</h3>
                </div>

                <div class="box-body">
                    <div class="row">
                        <div class="col-md-9">
                            {[ "type": "select", "label": "'.tr('Cliente').'", "name": "id_cliente_informazioni", "ajax-source": "clienti" ]}
                        </div>

                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary btn-block" style="margin-top:25px;" onclick="aggiungiPrezzi(this)">
                                <i class="fa fa-money"></i> '.tr('Prezzi').'
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane" id="fornitori">
            <p>'.tr("In questa sezione è possibile definire le caratteristiche di base dell'articolo in relazione fornitore di origine, come codice e prezzo di acquisto predefinito").'. '.tr("Queste informazioni saranno utilizzate in automatico per la compilazione dell'articolo al momento dell'inserimento in un documento di acquisto relativo al fornitore indicato, sovrascrivendo le impostazioni predefinite della sezione Acquisto per l'articolo").'.</p>
            <p>'.tr("Ogni fornitore, tra cui si evidenzia quello predefinito per l'articolo, può essere descritto una sola volta con le informazioni aggiuntive").'.</p>

            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">'.tr('Aggiungi informazioni per fornitore').'</h3>
                </div>

                <div class="box-body">
                    <div class="row">
                        <div class="col-md-9">
                            {[ "type": "select", "label": "'.tr('Fornitore').'", "name": "id_fornitore_informazioni", "ajax-source": "fornitori" ]}
                        </div>

                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary btn-block" style="margin-top:25px;" onclick="aggiungiPrezzi(this)">
                                <i class="fa fa-money"></i> '.tr('Prezzi').'
                            </button>

                            <button type="button" class="btn btn-primary btn-block" style="margin-top:25px;" onclick="aggiungiFornitore()">
                                <i class="fa fa-inbox"></i> '.tr('Dettagli').'
                            </button>
                        </div>
                    </div>
                </div>
            </div>';

$fornitori = DettaglioFornitore::where('id_articolo', $id_record)->get();
if (!$fornitori->isEmpty()) {
    echo '
            <h4>'.tr('Elenco fornitori').'</h4>
            <table class="table table-striped table-condensed table-bordered">
                <thead>
                    <tr>
                        <th>'.tr('Fornitore').'</th>
                        <th width="150">'.tr('Codice').'</th>
                        <th>'.tr('Descrizione').'</th>
                        <th class="text-center" width="210">'.tr('Q.tà minima ordinabile').'</th>
                        <th class="text-center" width="150">'.tr('Tempi di consegna').'</th>
                        <th class="text-center" width="150">'.tr('Prezzo acquisto').'</th>
                        <th class="text-center" width="70"></th>
                    </tr>
                </thead>

                <tbody>';

    foreach ($fornitori as $fornitore) {
        $anagrafica = $fornitore->anagrafica;

        echo '
                    <tr '.(($anagrafica->id == $articolo->id_fornitore) ? 'class="success"' : '').'>
                        <td>
                            '.Modules::link('Anagrafiche', $anagrafica->id, $anagrafica->ragione_sociale).'
                        </td>

                        <td class="text-center">
                            '.$fornitore['codice_fornitore'].'
                        </td>

                        <td>
                            '.$fornitore['descrizione'].'
                        </td>

                        <td class="text-right">
                            '.numberFormat($fornitore['qta_minima']).' '.$fornitore->articolo->um.'
                        </td>

                        <td class="text-right">
                            '.tr('_NUM_ gg', [
                                '_NUM_' => numberFormat($fornitore['giorni_consegna'], 0),
                            ]).'
                        </td>

                        <td class="text-right">
                            <span>'.moneyFormat($fornitore['prezzo_acquisto']).'</span>
                        </td>

                        <td class="text-center">
                            <a class="btn btn-secondary btn-xs btn-warning" onclick="modificaFornitore('.$fornitore['id'].', '.$anagrafica->id.')">
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
        </div>
    </div>
</div>

<script>
$(document).ready(function (){
    apriTab($("#clienti-tab")[0]);
});

function apriTab(link) {
    let element = $(link).closest("li");
    let parent = element.closest(".nav-tabs-custom");

    parent.find("ul > li").removeClass("active");
    element.addClass("active");

    let tab = $(link).data("tab");
    parent.find(".tab-pane").removeClass("active");
    parent.find(".tab-pane#" + tab).addClass("active");
}

function modificaPrezzi(id_anagrafica, direzione) {
    openModal("Modifica dettagli prezzi", "'.$structure->fileurl('dettaglio_prezzi.php').'?id_plugin='.$id_plugin.'&id_module='.$id_module.'&id_parent='.$id_record.'&id_articolo='.$id_record.'&id_anagrafica=" + id_anagrafica + "&direzione=" + direzione);
}

function aggiungiPrezzi(button) {
    let panel = $(button).closest(".box");
    let tab = panel.closest(".tab-pane");

    let direzione = tab.attr("id") === "fornitori" ? "uscita" : "entrata";
    let id_anagrafica = panel.find("select").val();

    if (id_anagrafica) {
        modificaPrezzi(id_anagrafica, direzione);
    } else {
        swal("'.tr('Errore').'", "'.tr('Nessuna anagrafica selezionato').'", "error");
    }
}

function modificaFornitore(id_riga, id_anagrafica) {
    openModal("Modifica dati fornitore", "'.$structure->fileurl('dettaglio_fornitore.php').'?id_plugin='.$id_plugin.'&id_module='.$id_module.'&id_parent='.$id_record.'&id_articolo='.$id_record.'&id_riga=" + id_riga + "&id_anagrafica=" + id_anagrafica);
}

function aggiungiFornitore() {
    let id_fornitore = $("#id_fornitore_informazioni").val();
    if (id_fornitore) {
        modificaFornitore("", id_fornitore);
    } else {
        swal("'.tr('Errore').'", "'.tr('Nessun fornitore selezionato').'", "error");
    }
}
</script>';
