<?php

include_once __DIR__.'/../../core.php';

$idcontratto = $get['idcontratto'];
$idriga = $get['idriga'];

// Info contratto
$q = 'SELECT * FROM co_contratti WHERE id='.prepare($idcontratto);
$rs = $dbo->fetchArray($q);
$numero = $rs[0]['numero'];
$idanagrafica = $rs[0]['idanagrafica'];

if (empty($idriga)) {
    $op = 'addriga';
    $button = tr('Aggiungi');

    // valori default
    $descrizione = '';
    $qta = 1;
    $um = '';
    $prezzo = 0;
    $sconto = 0;

    // Leggo l'iva predefinita dall'anagrafica e se non c'è leggo quella predefinita generica
    $rsa = $dbo->fetchArray('SELECT idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
    $idiva = (!empty($rsa[0]['idiva'])) ? $rsa[0]['idiva'] : get_var('Iva predefinita');
} else {
    $op = 'editriga';
    $button = tr('Modifica');

    $rsr = $dbo->fetchArray('SELECT * FROM co_righe2_contratti WHERE idcontratto='.prepare($idcontratto).' AND id='.prepare($idriga));

    $descrizione = $rsr[0]['descrizione'];
    $qta = $rsr[0]['qta'];
    $um = $rsr[0]['um'];
    $idiva = $rsr[0]['idiva'];
    $prezzo = $rsr[0]['subtotale'] / $rsr[0]['qta'];
    $sconto = $rsr[0]['sconto'];
}

echo '
<form action="'.$rootdir.'/editor.php?id_module='.Modules::getModule('Contratti')['id'].'&id_record='.$idcontratto.'" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="idriga" value="'.$idriga.'">
    <input type="hidden" name="backto" value="record-edit">';

// Descrizione
echo '
    <div class="col-md-12">
        {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "value": "'.$descrizione.'", "required": 1 ]}
    </div>';

// Iva

echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva_articolo", "required": 1, "values": "query=SELECT id, descrizione FROM co_iva ORDER BY descrizione ASC", "value": "'.$idiva.'" ]}
    </div>';

// Quantità
echo '
    <div class="col-md-4">
        {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "value": "'.$qta.'", "required": 1, "decimals": "qta" ]}
    </div>';

// Unità di misura
echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr('Unità di misura').'", "icon-after": "add|'.Modules::getModule('Unità di misura')['id'].'", "name": "um", "value": "'.$um.'", "ajax-source": "misure" ]}
    </div>';

/*
if (!empty($idriga)) {
//Rivalsa INPS
if( get_var("Percentuale rivalsa INPS") != "" ){
    echo "		<div class='col-md-3'>\n";
    echo "			<label>Rivalsa INPS:</label>\n";
    echo "			<select id='idrivalsainps' class='superselect' name=\"idrivalsainps\">\n";
    echo "				<option value=''>-</option>\n";

    $query = "SELECT * FROM co_rivalsainps";
    $rs = $dbo->fetchArray($query);
    for( $i=0; $i<sizeof($rs); $i++ ){
        ( $rs[$i]['id'] == $rsr[$i]['idrivalsainps'] ) ? $attr='selected="true"' : $attr='';
        echo "				<option value='".$rs[$i]['id']."' ".$attr.">".$rs[$i]['descrizione']."</option>\n";
    }

    echo "			</select>\n";
    echo "		</div>\n";
}

//Ritenuta d'acconto
if( get_var("Percentuale ritenuta d'acconto") != "" ){
    echo "		<div class='col-md-3'>\n";
    echo "			<label>Ritenuta d'acconto:</label>\n";
    echo "			<select id='idritenutaacconto' class='superselect' name=\"idritenutaacconto\">\n";
    echo "				<option value=''>-</option>\n";

    $query = "SELECT * FROM co_ritenutaacconto";
    $rs = $dbo->fetchArray($query);
    for( $i=0; $i<sizeof($rs); $i++ ){
        ( $rs[$i]['id'] == $rsr[$i]['idritenutaacconto'] ) ? $attr='selected="true"' : $attr='';
        echo "				<option value='".$rs[$i]['id']."' ".$attr.">".$rs[$i]['descrizione']."</option>\n";
    }

    echo "			</select>\n";
    echo "		</div>\n";
}
}
*/

// Costo unitario
echo '
    <div class="col-md-6">
        {[ "type": "number", "label": "'.tr('Costo unitario').'", "name": "prezzo", "required": 1, "value": "'.$prezzo.'", "icon-after": "&euro;" ]}
    </div>';

// Sconto unitario
echo '
    <div class="col-md-6">
        {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "value": "'.$sconto.'", "icon-after": "&euro;" ]}
    </div>';

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.$button.'</button>
		</div>
    </div>
</form>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';
