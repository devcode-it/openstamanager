<?php

include_once __DIR__.'/../../core.php';

$module = Modules::getModule($id_module);

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
    $conti = 'conti-vendite';
} else {
    $dir = 'uscita';
    $conti = 'conti-acquisti';
}

$record = $dbo->fetchArray('SELECT * FROM co_documenti WHERE id='.prepare($id_record));
$numero = ($record[0]['numero_esterno'] != '') ? $record[0]['numero_esterno'] : $record[0]['numero'];
$idanagrafica = $record[0]['idanagrafica'];

$idriga = get('idriga');

// Info riga
$q = "SELECT * FROM co_righe_documenti WHERE iddocumento=".prepare($id_record)." AND id=".prepare($idriga);
$rsr = $dbo->fetchArray($q);
$sconto = $rsr[0]['sconto_unitario'];
$tipo_sconto = $rsr[0]['tipo_sconto'];
$idarticolo = $rsr[0]['idarticolo'];
$idconto = $rsr[0]['idconto'];

echo '
<p>'.str_replace('_NUM_', $numero, _('Documento numero _NUM_')).'</p>

<form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="editriga">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="idriga" value="'.$idriga.'">
    <input type="hidden" name="dir" value="'.$dir.'">';

// Descrizione
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'._('Descrizione').'", "name": "descrizione", "required": 1, "value": "'.$rsr[0]['descrizione'].'" ]}
        </div>
    </div>';

if (get_var('Percentuale rivalsa INPS') != '' || get_var("Percentuale ritenuta d'acconto") != '' || $dir == 'uscita') {
    echo '
    <div class="row">';

    // Rivalsa INPS
    if (get_var('Percentuale rivalsa INPS') != '' || $dir == 'uscita') {
        echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'._('Rivalsa INPS').'", "name": "idrivalsainps", "value": "'.$rsr[0]['idrivalsainps'].'", "values": "query=SELECT * FROM co_rivalsainps" ]}
        </div>';
    }

    // Ritenuta d'acconto
    if (get_var("Percentuale ritenuta d'acconto") != '' || $dir == 'uscita') {
        echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'._("Ritenuta d'acconto").'", "name": "idritenutaacconto", "value": "'.$rsr[0]['idritenutaacconto'].'", "values": "query=SELECT * FROM co_ritenutaacconto" ]}
        </div>';
    }

    echo '
    </div>';
}

// Iva
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'._('Iva').'", "name": "idiva", "required": 1, "value": "'.$rsr[0]['idiva'].'", "values": "query=SELECT * FROM co_iva ORDER BY descrizione ASC" ]}
        </div>';

echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'._('Conto').'", "name": "idconto", "required": 1, "value": "'.$idconto.'", "ajax-source": "'.$conti.'" ]}
        </div>
    </div>';

// Quantità
echo '
    <div class="row">
        <div class="col-md-3">
            {[ "type": "number", "label": "'._('Q.tà').'", "name": "qta", "required": 1, "value": "'.$rsr[0]['qta'].'", "decimals": "qta" ]}
        </div>';

// Unità di misura
echo '
        <div class="col-md-3">
            {[ "type": "select", "label": "'._('Unità di misura').'", "icon-after": "add|'.Modules::getModule('Unità di misura')['id'].'", "name": "um", "ajax-source": "misure", "value": "'.$rsr[0]['um'].'" ]}
        </div>';

// Costo unitario
echo '
        <div class="col-md-3">
            {[ "type": "number", "label": "'._('Costo unitario').'", "name": "prezzo", "required": 1, "value": "'.($rsr[0]['subtotale']/$rsr[0]['qta']).'", "icon-after": "&euro;" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-3">
            {[ "type": "number", "label": "'._('Sconto unitario').'", "name": "sconto", "value": "'.$sconto.'", "icon-after": "choice|untprc|'.$tipo_sconto.'" ]}
        </div>
    </div>';

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '._('Aggiungi').'</button>
		</div>
    </div>
</form>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';
