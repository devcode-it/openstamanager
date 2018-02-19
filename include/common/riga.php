<?php

include_once __DIR__.'/../../core.php';

// Descrizione
echo App::internalLoad('descrizione.php', $result, $options);

// Informazioni aggiuntive per Fatture
if ($module['name'] == 'Fatture di acquisto' || $module['name'] == 'Fatture di vendita') {
    // Percentuale rivalsa INPS e Percentuale ritenuta d'acconto
    if (get_var('Percentuale rivalsa INPS') != '' || get_var("Percentuale ritenuta d'acconto") != '' || $options['dir'] == 'uscita') {
        echo '
    <div class="row">';

        // Rivalsa INPS
        if (get_var('Percentuale rivalsa INPS') != '' || $options['dir'] == 'uscita') {
            echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Rivalsa INPS').'", "name": "idrivalsainps", "value": "'.$result['idrivalsainps'].'", "values": "query=SELECT * FROM co_rivalsainps", "required": '.intval($options['dir'] != 'uscita').' ]}
        </div>';
        }

        // Ritenuta d'acconto
        if (get_var("Percentuale ritenuta d'acconto") != '' || $options['dir'] == 'uscita') {
            echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr("Ritenuta d'acconto").'", "name": "idritenutaacconto", "value": "'.$result['idritenutaacconto'].'", "values": "query=SELECT * FROM co_ritenutaacconto", "required": '.intval($options['dir'] != 'uscita').' ]}
        </div>';
        }

        echo '
    </div>';
    }

    // Conto
    echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Conto').'", "name": "idconto", "required": 1, "value": "'.$result['idconto'].'", "ajax-source": "'.$options['conti'].'" ]}
        </div>
    </div>';
}

// Iva
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$result['idiva'].'", "values": "query=SELECT * FROM co_iva ORDER BY descrizione ASC" ]}
        </div>';

// Quantità
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "required": 1, "value": "'.$result['qta'].'", "decimals": "qta" ]}
        </div>';

// Unità di misura
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Unità di misura').'", "icon-after": "add|'.Modules::get('Unità di misura')['id'].'", "name": "um", "value": "'.$result['um'].'", "ajax-source": "misure" ]}
        </div>
    </div>';

    // Costo unitario
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "number", "label": "'.tr('Costo unitario').'", "name": "prezzo", "value": "'.$result['prezzo'].'", "required": 1, "icon-after": "&euro;" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-6">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "value": "'.$result['sconto_unitario'].'", "icon-after": "choice|untprc|'.$result['tipo_sconto'].'" ]}
        </div>
    </div>';
