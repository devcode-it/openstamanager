<?php

use Modules\Anagrafiche\Anagrafica;
use Plugins\FornitoriArticolo\Dettaglio;

include_once __DIR__.'/../../core.php';

$id_articolo = get('id_articolo');
$id_anagrafica = get('id_anagrafica');
$anagrafica = Anagrafica::find($id_anagrafica);

$id_riga = get('id_riga');
$fornitore = [];
if (!empty($id_riga)) {
    $fornitore = Dettaglio::find($id_riga);
}

echo '
<p>'.tr('Informazioni relative al fornitore _NAME_', [
    '_NAME_' => $anagrafica->ragione_sociale,
]).'.</p>

<form action="" method="post">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="update_fornitore">

    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_riga" value="'.$id_riga.'">
    <input type="hidden" name="id_anagrafica" value="'.$id_anagrafica.'">
    <input type="hidden" name="id_articolo" value="'.$id_articolo.'">

    <div class="row">
        <div class="col-md-12">
            {[ "type": "text", "label": "'.tr('Codice fornitore').'", "name": "codice_fornitore", "required": 1, "value": "'.$fornitore['codice_fornitore'].'" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "required": 1, "value": "'.$fornitore['descrizione'].'" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Prezzo acquisto').'", "name": "prezzo_acquisto", "required": 1, "value": "'.$fornitore['prezzo_acquisto'].'", "icon-after": "&euro;" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Qta minima ordinabile').'", "name": "qta_minima", "required": 0, "value": "'.$fornitore['qta_minima'].'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "text", "label": "'.tr('Tempi di consegna').'", "name": "giorni_consegna", "class": "text-right", "required": 0, "value": "'.$fornitore['giorni_consegna'].'", "icon-after": "giorni" ]}
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="row">
        <div class="col-md-12">
            <button class="btn btn-primary pull-right">
                <i class="fa fa-edit"></i> '.tr('Modifica').'
            </button>
        </div>
    </div>
</form>

<script>$(document).ready(init);</script>';
