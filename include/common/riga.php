<?php

// Descrizione
echo App::internalLoad('descrizione.php', $result, $options);

$show_idrivalsainps = 0;
$show_idritenutaacconto = 0;
$show_calcolo_ritenutaacconto = 0;
$idrivalsainps = 0;
$idritenutaacconto = 0;
$calcolo_ritenutaacconto = 0;

// Informazioni aggiuntive per Fatture
if ($module['name'] == 'Fatture di acquisto' || $module['name'] == 'Fatture di vendita') {
    
    // Percentuale rivalsa INPS e Percentuale ritenuta d'acconto
    if ($options['action'] == 'edit'){
        
        if($options['dir'] == 'uscita'){
            $show_idrivalsainps = 1;
            $show_idritenutaacconto = 1;
            $show_calcolo_ritenutaacconto = 1;
        }
        else if (($options['dir'] == 'entrata' && ( get_var('Percentuale rivalsa INPS') != '' || get_var("Percentuale ritenuta d'acconto") != ''))) {
            if( get_var('Percentuale rivalsa INPS') != '' ){ $show_idrivalsainps = 1; }else{ $show_idrivalsainps = 0; }
            if( get_var("Percentuale ritenuta d'acconto") != '' ){ $show_idritenutaacconto = 1; }else{ $show_idritenutaacconto = 0; }
            if( get_var("Percentuale ritenuta d'acconto") != '' ){ $show_calcolo_ritenutaacconto = 1; }else{ $show_calcolo_ritenutaacconto = 0; }
        }
        
        $idrivalsainps = $result['idrivalsainps'];
        $idritenutaacconto = $result['idritenutaacconto'];
        $calcolo_ritenutaacconto = $result['calcolo_ritenutaacconto'];
        
    }
    
    else if ($options['action'] == 'add'){
        
        if($options['dir'] == 'uscita'){
            $show_idrivalsainps = 1;
            $show_idritenutaacconto = 1;
            $show_calcolo_ritenutaacconto = 1;
            
            $idrivalsainps = "";
            $idritenutaacconto = "";
            $calcolo_ritenutaacconto = get_var("Metodologia calcolo ritenuta d'acconto predefinito");
        }
        else if ($options['dir'] == 'entrata' && $options['op']=='addriga' && ( get_var('Percentuale rivalsa INPS') != '' || get_var("Percentuale ritenuta d'acconto") != '')) {
            if( get_var('Percentuale rivalsa INPS') != '' ){ $show_idrivalsainps = 1; }else{ $show_idrivalsainps = 0; }
            if( get_var("Percentuale ritenuta d'acconto") != '' ){ $show_idritenutaacconto = 1; }else{ $show_idritenutaacconto = 0; }
            if( get_var("Percentuale ritenuta d'acconto") != '' ){ $show_calcolo_ritenutaacconto = 1; }else{ $show_calcolo_ritenutaacconto = 0; }
            
            $idrivalsainps = get_var('Percentuale rivalsa INPS');
            $idritenutaacconto = get_var("Percentuale ritenuta d'acconto");
            $calcolo_ritenutaacconto = get_var("Metodologia calcolo ritenuta d'acconto predefinito");
        }
        //Caso particolare per aggiunta articolo in fatture di vendita
        else if($options['dir'] == 'entrata' && $options['op']=='addarticolo' && ( get_var('Percentuale rivalsa INPS') != '' || get_var("Percentuale ritenuta d'acconto") != '')){
            if( get_var('Percentuale rivalsa INPS') != '' ){ $show_idrivalsainps = 1; }else{ $show_idrivalsainps = 0; }
            if( get_var("Percentuale ritenuta d'acconto") != '' ){ $show_idritenutaacconto = 1; }else{ $show_idritenutaacconto = 0; }
            if( get_var("Percentuale ritenuta d'acconto") != '' ){ $show_calcolo_ritenutaacconto = 1; }else{ $show_calcolo_ritenutaacconto = 0; }
            
            $idrivalsainps = "";
            $idritenutaacconto = get_var("Percentuale ritenuta d'acconto");
            $calcolo_ritenutaacconto = get_var("Metodologia calcolo ritenuta d'acconto predefinito");
        }
        
    }
    
    if($show_idrivalsainps==1 || $show_idritenutaacconto==1){
        echo '
<div class="row">';

        // Rivalsa INPS
        if ( $show_idrivalsainps == 1 ) {
            echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr('Rivalsa INPS').'", "name": "idrivalsainps", "value": "'.$idrivalsainps.'", "values": "query=SELECT * FROM co_rivalsainps" ]}
    </div>';
        }

        // Ritenuta d'acconto
        if ( $show_idritenutaacconto == 1 ) {
            echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr("Ritenuta d'acconto").'", "name": "idritenutaacconto", "value": "'.$idritenutaacconto.'", "values": "query=SELECT * FROM co_ritenutaacconto" ]}
    </div>';
        }
        
        //Calcola ritenuta d'acconto su
        if ( $show_calcolo_ritenutaacconto == 1 ) {
            echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr("Calcola ritenuta d'acconto su").'", "name": "calcolo_ritenutaacconto", "value": "'.$calcolo_ritenutaacconto.'", "values": "list=\"Imponibile\":\"Imponibile\", \"Imponibile + rivalsa inps\":\"Imponibile + rivalsa inps\"", "required": "1" ]}
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
