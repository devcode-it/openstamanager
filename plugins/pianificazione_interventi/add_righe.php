<?php

include_once __DIR__.'/../../core.php';

$plugin = Plugins::get($id_plugin);

$idriga = filter('idriga');

if (empty($idriga)) {
    $op = 'addriga';
    $button = '<i class="fa fa-plus"></i> '.tr('Aggiungi');

    // valori default
    $descrizione = '';
    $qta = 1;
    $um = '';
    $prezzo_vendita = '0';
    $prezzo_acquisto = '0';
    $idiva = setting('Iva predefinita');

    if (!empty($rs[0]['prc_guadagno'])) {
        $sconto_unitario = $rs[0]['prc_guadagno'];
        $tipo_sconto = 'PRC';
    }
} else {
    $op = 'editriga';
    $button = '<i class="fa fa-edit"></i> '.tr('Modifica');

    // carico record da modificare
    $q = 'SELECT * FROM co_promemoria_righe WHERE id='.prepare($idriga);
    $rsr = $dbo->fetchArray($q);

    $descrizione = $rsr[0]['descrizione'];
    $qta = $rsr[0]['qta'];
    $um = $rsr[0]['um'];
    $idiva = $rsr[0]['idiva'];
    $prezzo_vendita = $rsr[0]['prezzo_vendita'];
    $prezzo_acquisto = $rsr[0]['prezzo_acquisto'];

    $sconto_unitario = $rsr[0]['sconto_unitario'];
    $tipo_sconto = $rsr[0]['tipo_sconto'];
}

echo '
<form id="add-righe" action="'.$rootdir.'/actions.php" method="post">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
	<input type="hidden" name="id_record" value="'.$id_record.'">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="idriga" value="'.$idriga.'">';

// Descrizione
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "id": "descrizione_riga", "name": "descrizione", "required": 1, "value": '.json_encode($descrizione).' ]}
        </div>
    </div>
    <br>';

// Quantità
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "required": 1, "value": "'.$qta.'", "decimals": "qta" ]}
        </div>';

// Unità di misura
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Unità di misura').'", "name": "um", "value": "'.$um.'", "ajax-source": "misure" ]}
        </div>';

// Iva
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$idiva.'", "ajax-source": "iva" ]}
        </div>
    </div>';

// Prezzo di acquisto
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Prezzo di acquisto (un.)').'", "name": "prezzo_acquisto", "required": 1, "value": "'.$prezzo_acquisto.'", "icon-after": "'.currency().'" ]}
        </div>';

// Prezzo di vendita
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Prezzo di vendita (un.)').'", "name": "prezzo_vendita", "required": 1, "value": "'.$prezzo_vendita.'", "icon-after": "'.currency().'" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "icon-after": "choice|untprc|'.$tipo_sconto.'", "value": "'.$sconto_unitario.'" ]}
        </div>
    </div>';

echo '
    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary pull-right">'.$button.'</button>
        </div>
    </div>
</form>';

echo '
<script src="'.$rootdir.'/lib/init.js"></script>';

echo '
<script type="text/javascript">
    $(document).ready(function() {
        $("#add-righe").ajaxForm({
            success: function(){
                $("#bs-popup2").modal("hide");

                refreshRighe('.$id_record.');
            }
        });
    });
</script>';
