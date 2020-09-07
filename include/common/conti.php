<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Informazioni aggiuntive per Fatture
if ($module['name'] != 'Fatture di acquisto' && $module['name'] != 'Fatture di vendita') {
    return;
}

if ($options['dir'] == 'entrata') {
    $show_rivalsa = ((setting('Percentuale rivalsa') != '') or (!empty($result['idrivalsainps'])));
    $show_ritenuta_acconto = ((setting("Percentuale ritenuta d'acconto") != '') or (!empty($result['idritenutaacconto'])));
    $show_ritenuta_acconto |= !empty($options['id_ritenuta_acconto_predefined']);
} else {
    $show_rivalsa = 1;
    $show_ritenuta_acconto = 1;
}

// Percentuale rivalsa e Percentuale ritenuta d'acconto
if ($options['action'] == 'edit') {
    $id_rivalsa_inps = $result['idrivalsainps'];
    $id_ritenuta_acconto = $result['idritenutaacconto'];
    $calcolo_ritenuta_acconto = $result['calcolo_ritenuta_acconto'];
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
        $id_rivalsa_inps = ($options['op'] == 'addarticolo') ? '' : setting('Percentuale rivalsa');

        $id_ritenuta_acconto = $options['id_ritenuta_acconto_predefined'] ?: setting("Percentuale ritenuta d'acconto");
    }
}

$calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto ?: setting("Metodologia calcolo ritenuta d'acconto predefinito");

if ($show_rivalsa == 1 || $show_ritenuta_acconto == 1) {
    echo '
<div class="row">';

    // Rivalsa INPS
    if ($show_rivalsa == 1) {
        echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr('Rivalsa').'", "name": "id_rivalsa_inps", "value": "'.$id_rivalsa_inps.'", "values": "query=SELECT * FROM co_rivalse", "help": "'.(($options['dir'] == 'entrata') ? setting('Tipo Cassa Previdenziale') : null).'" ]}
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
    if ($show_ritenuta_acconto == 1) {
        echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr("Calcola ritenuta d'acconto su").'", "name": "calcolo_ritenuta_acconto", "value": "'.$calcolo_ritenuta_acconto.'", "values": "list=\"IMP\":\"Imponibile\", \"IMP+RIV\":\"Imponibile + rivalsa\"", "required": "1" ]}
    </div>';
    }

    echo '
</div>';
}

if (!empty($options['show-ritenuta-contributi']) || empty($options['hide_conto'])) {
    $width = !empty($options['show-ritenuta-contributi']) && empty($options['hide_conto']) ? 6 : 12;

    echo '
<div class="row">';

    // Ritenuta contributi
    if (!empty($options['show-ritenuta-contributi'])) {
        echo '
    <div class="col-md-'.$width.'">
        {[ "type": "checkbox", "label": "'.tr('Ritenuta contributi').'", "name": "ritenuta_contributi", "value": "'.$result['ritenuta_contributi'].'" ]}
    </div>';
    }

    // Conto
    if (empty($options['hide_conto'])) {
        echo '
    <div class="col-md-'.$width.'">
        {[ "type": "select", "label": "'.tr('Conto').'", "name": "idconto", "required": 1, "value": "'.$result['idconto'].'", "ajax-source": "'.$options['conti'].'" ]}
    </div>';
    }

    echo '
</div>';
}
