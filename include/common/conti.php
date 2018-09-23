<?php

$show_idrivalsainps = 0;
$show_idritenutaacconto = 0;
$show_calcolo_ritenutaacconto = 0;
$idrivalsainps = 0;
$idritenutaacconto = 0;
$calcolo_ritenutaacconto = 0;

// Informazioni aggiuntive per Fatture
if ($module['name'] == 'Fatture di acquisto' || $module['name'] == 'Fatture di vendita') {
    // Percentuale rivalsa INPS e Percentuale ritenuta d'acconto
    if ($options['action'] == 'edit') {
        if ($options['dir'] == 'uscita') {
            //Luca S. questi campi non dovrebbero essere impostati a 1 di default, ma solo se il fornitore ha effettivamente rivalsa inps o ritenuta
            $show_idrivalsainps = 1;
            $show_idritenutaacconto = 1;
            $show_calcolo_ritenutaacconto = 1;
        } elseif (($options['dir'] == 'entrata' && (setting('Percentuale rivalsa INPS') != '' || setting("Percentuale ritenuta d'acconto") != ''))) {
            if (setting('Percentuale rivalsa INPS') != '') {
                $show_idrivalsainps = 1;
            } else {
                $show_idrivalsainps = 0;
            }
            if (setting("Percentuale ritenuta d'acconto") != '') {
                $show_idritenutaacconto = 1;
            } else {
                $show_idritenutaacconto = 0;
            }
            if (setting("Percentuale ritenuta d'acconto") != '') {
                $show_calcolo_ritenutaacconto = 1;
            } else {
                $show_calcolo_ritenutaacconto = 0;
            }
        }

        $idrivalsainps = $result['idrivalsainps'];
        $idritenutaacconto = $result['idritenutaacconto'];
        $calcolo_ritenutaacconto = $result['calcolo_ritenutaacconto'];
    } elseif ($options['action'] == 'add') {
        if ($options['dir'] == 'uscita') {
            $show_idrivalsainps = 1;
            $show_idritenutaacconto = 1;
            $show_calcolo_ritenutaacconto = 1;

            // Luca S. questi campi non dovrebbero essere definiti all'interno della scheda fornitore?
            $idrivalsainps = '';
            $idritenutaacconto = '';
            // questo campo non andrebbe letto da impostazioni
            $calcolo_ritenutaacconto = setting("Metodologia calcolo ritenuta d'acconto predefinito");
        } elseif ($options['dir'] == 'entrata' && $options['op'] == 'addriga' && (setting('Percentuale rivalsa INPS') != '' || setting("Percentuale ritenuta d'acconto") != '')) {
            if (setting('Percentuale rivalsa INPS') != '') {
                $show_idrivalsainps = 1;
            } else {
                $show_idrivalsainps = 0;
            }
            if (setting("Percentuale ritenuta d'acconto") != '') {
                $show_idritenutaacconto = 1;
            } else {
                $show_idritenutaacconto = 0;
            }
            if (setting("Percentuale ritenuta d'acconto") != '') {
                $show_calcolo_ritenutaacconto = 1;
            } else {
                $show_calcolo_ritenutaacconto = 0;
            }

            $idrivalsainps = setting('Percentuale rivalsa INPS');
            $idritenutaacconto = $result['idritenutaacconto'] ?: setting("Percentuale ritenuta d'acconto");
            $calcolo_ritenutaacconto = setting("Metodologia calcolo ritenuta d'acconto predefinito");
        }
        // Caso particolare per aggiunta articolo in fatture di vendita
        elseif ($options['dir'] == 'entrata' && $options['op'] == 'addarticolo' && (setting('Percentuale rivalsa INPS') != '' || setting("Percentuale ritenuta d'acconto") != '')) {
            if (setting('Percentuale rivalsa INPS') != '') {
                $show_idrivalsainps = 1;
            } else {
                $show_idrivalsainps = 0;
            }
            if (setting("Percentuale ritenuta d'acconto") != '') {
                $show_idritenutaacconto = 1;
            } else {
                $show_idritenutaacconto = 0;
            }
            if (setting("Percentuale ritenuta d'acconto") != '') {
                $show_calcolo_ritenutaacconto = 1;
            } else {
                $show_calcolo_ritenutaacconto = 0;
            }

            $idrivalsainps = '';
            $idritenutaacconto = setting("Percentuale ritenuta d'acconto");
            $calcolo_ritenutaacconto = setting("Metodologia calcolo ritenuta d'acconto predefinito");
        }
    }

    if ($show_idrivalsainps == 1 || $show_idritenutaacconto == 1) {
        echo '
<div class="row">';

        // Rivalsa INPS
        if ($show_idrivalsainps == 1) {
            echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr('Rivalsa INPS').'", "name": "idrivalsainps", "value": "'.$idrivalsainps.'", "values": "query=SELECT * FROM co_rivalsainps" ]}
    </div>';
        }

        // Ritenuta d'acconto
        if ($show_idritenutaacconto == 1) {
            echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr("Ritenuta d'acconto").'", "name": "idritenutaacconto", "value": "'.$idritenutaacconto.'", "values": "query=SELECT * FROM co_ritenutaacconto" ]}
    </div>';
        }

        // Calcola ritenuta d'acconto su
        if ($show_calcolo_ritenutaacconto == 1) {
            echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr("Calcola ritenuta d'acconto su").'", "name": "calcolo_ritenutaacconto", "value": "'.((empty($calcolo_ritenutaacconto)) ? 'Imponibile' : $calcolo_ritenutaacconto).'", "values": "list=\"Imponibile\":\"Imponibile\", \"Imponibile + rivalsa inps\":\"Imponibile + rivalsa inps\"", "required": "1" ]}
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
