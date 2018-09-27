<?php

// Informazioni aggiuntive per Fatture
if ($module['name'] != 'Fatture di acquisto' && $module['name'] != 'Fatture di vendita') {
    return;
}

if ($options['dir'] == 'entrata') {
    $show_rivalsa_inps = (setting('Percentuale rivalsa INPS') != '');
    $show_ritenuta_acconto = (setting("Percentuale ritenuta d'acconto") != '');

    $show_ritenuta_acconto |= !empty($options['id_ritenuta_acconto_predefined']);
} else {
    $show_rivalsa_inps = 1;
    $show_ritenuta_acconto = 1;
}

$show_calcolo_ritenuta_acconto = $show_ritenuta_acconto;

// Percentuale rivalsa INPS e Percentuale ritenuta d'acconto
if ($options['action'] == 'edit') {
    $id_rivalsa_inps = $result['idrivalsainps'];
    $id_ritenuta_acconto = $result['idritenutaacconto'];
    $calcolo_ritenuta_acconto = $result['calcolo_ritenutaacconto'];
} elseif ($options['action'] == 'add') {
    // Fattura di acquisto
    if ($options['dir'] == 'uscita') {
        // TODO: Luca S. questi campi non dovrebbero essere definiti all'interno della scheda fornitore?
        $id_rivalsa_inps = '';
        $id_ritenuta_acconto = '';
    }
    // Fattura di vendita
    elseif ($options['dir'] == 'entrata') {
        // Caso particolare per aggiunta articolo
        $id_rivalsa_inps = ($options['op'] == 'addarticolo') ? '' : setting('Percentuale rivalsa INPS');

        $id_ritenuta_acconto = $options['id_ritenuta_acconto_predefined'] ?: setting("Percentuale ritenuta d'acconto");
    }
}

$calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto ?: setting("Metodologia calcolo ritenuta d'acconto predefinito");

if ($show_rivalsa_inps == 1 || $show_ritenuta_acconto == 1) {
    echo '
<div class="row">';

    // Rivalsa INPS
    if ($show_rivalsa_inps == 1) {
        echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr('Rivalsa INPS').'", "name": "id_rivalsa_inps", "value": "'.$id_rivalsa_inps.'", "values": "query=SELECT * FROM co_rivalsainps" ]}
    </div>';
    }

    // Ritenuta d'acconto
    if ($show_ritenuta_acconto == 1) {
        echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr("Ritenuta d'acconto").'", "name": "id_ritenuta_acconto", "value": "'.$id_ritenuta_acconto.'", "values": "query=SELECT * FROM co_ritenutaacconto" ]}
    </div>';
    }

    // Calcola ritenuta d'acconto su
    if ($show_calcolo_ritenuta_acconto == 1) {
        echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr("Calcola ritenuta d'acconto su").'", "name": "calcolo_ritenuta_acconto", "value": "'.$calcolo_ritenuta_acconto.'", "values": "list=\"Imponibile\":\"Imponibile\", \"Imponibile + rivalsa inps\":\"Imponibile + rivalsa inps\"", "required": "1" ]}
    </div>';
    }

    echo '
</div>';
}

// Conto
if (empty($options['hide_conto'])) {
    echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Conto').'", "name": "idconto", "required": 1, "value": "'.$result['idconto'].'", "ajax-source": "'.$options['conti'].'" ]}
        </div>
    </div>';
}
