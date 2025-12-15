<?php

include_once __DIR__.'/../../../core.php';

$idrifornimento = get('idrifornimento');
$idviaggio = get('idviaggio');

// Se è presente idrifornimento, recupero i dati per la modifica
if (!empty($idrifornimento)) {
    $rifornimento = $dbo->fetchOne('SELECT * FROM an_automezzi_rifornimenti WHERE id='.prepare($idrifornimento));
    $op = 'editrifornimento';
    $button_icon = 'fa-edit';
    $button_text = tr('Modifica');
} else {
    // Valori di default per nuovo rifornimento
    $rifornimento = [
        'idviaggio' => $idviaggio,
        'data' => '-now-',
        'luogo' => '',
        'id_carburante' => '',
        'quantita' => '',
        'costo' => '',
        'id_gestore' => '',
        'codice_carta' => '',
        'km' => '',
    ];
    $op = 'addrifornimento';
    $button_icon = 'fa-plus';
    $button_text = tr('Aggiungi');
}

/*
    Form di inserimento/modifica rifornimento
*/
echo '
<form id="rifornimento_form" action="" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">';

if (!empty($idrifornimento)) {
    echo '
    <input type="hidden" name="idrifornimento" value="'.$idrifornimento.'">';
} else {
    echo '
    <input type="hidden" name="idviaggio" value="'.$idviaggio.'">';
}

echo '
    <div class="row">';

// Data
echo '
        <div class="col-md-6">
            {[ "type": "timestamp", "label": "'.tr('Data').'", "name": "data", "required": 1, "value": "'.$rifornimento['data'].'" ]}
        </div>';

// Luogo
echo '
        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Luogo').'", "name": "luogo", "required": 1, "value": "'.$rifornimento['luogo'].'" ]}
        </div>';

echo '
    </div>
    
    <div class="row">';

// Tipo di carburante
echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Tipo di carburante').'", "name": "id_carburante", "required": 1, "value": "'.$rifornimento['id_carburante'].'", "ajax-source": "tipi-carburante" ]}
        </div>';

// Quantità
echo '
        <div class="col-md-6">
            {[ "type": "number", "label": "'.tr('Quantità').'", "name": "quantita", "required": 1, "decimals": "qta", "value": "'.$rifornimento['quantita'].'" ]}
        </div>';

echo '
    </div>
    
    <div class="row">';

// Costo
echo '
        <div class="col-md-6">
            {[ "type": "number", "label": "'.tr('Costo').'", "name": "costo", "required": 1, "icon-after": "€", "value": "'.$rifornimento['costo'].'" ]}
        </div>';

// Gestore
echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Gestore').'", "name": "id_gestore", "required": 1, "value": "'.$rifornimento['id_gestore'].'", "ajax-source": "gestori-carburante" ]}
        </div>';

echo '
    </div>
    
    <div class="row">';

// Codice carta carburante
echo '
        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Codice carta carburante').'", "name": "codice_carta", "value": "'.$rifornimento['codice_carta'].'" ]}
        </div>';

// Chilometraggio
echo '
        <div class="col-md-6">
            {[ "type": "number", "label": "'.tr('Chilometraggio').'", "name": "km", "required": 1, "value": "'.$rifornimento['km'].'", "icon-after": "km", "decimals": 0 ]}
        </div>';

echo '
    </div>';

echo '
    <!-- PULSANTI -->
    <div class="modal-footer">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary pull-right"><i class="fa '.$button_icon.'"></i> '.$button_text.'</button>
        </div>
    </div>
</form>';

if ($rifornimento['id']) {
    echo '
    <hr>
    {( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$", "key": "an_automezzi_rifornimenti:'.$rifornimento['id'].'" )}';
}

echo '
<script>
    $(document).ready(function(){init();});
</script>';

