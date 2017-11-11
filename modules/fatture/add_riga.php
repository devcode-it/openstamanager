<?php


include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
    $conti = 'conti-vendite';
} else {
    $dir = 'uscita';
    $conti = 'conti-acquisti';
}

$record = $dbo->fetchArray('SELECT * FROM co_documenti WHERE id='.prepare($id_record));
$numero = ($record[0]['numero_esterno'] != '') ? $record[0]['numero_esterno'] : $record[0]['numero'];
$idconto = $record[0]['idconto'];
$idanagrafica = $record[0]['idanagrafica'];

/*
    Form di inserimento riga documento
*/
echo '
<p>'.tr('Documento numero _NUM_', [
    '_NUM_' => $numero,
]).'</p>

<form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="addriga">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="dir" value="'.$dir.'">';

// Descrizione
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "required": 1 ]}
        </div>
    </div>';

if (get_var('Percentuale rivalsa INPS') != '' || get_var("Percentuale ritenuta d'acconto") != '' || $dir == 'uscita') {
    echo '
    <div class="row">';

    // Rivalsa INPS
    if (get_var('Percentuale rivalsa INPS') != '' || $dir == 'uscita') {
        echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Rivalsa INPS').'", "name": "idrivalsainps",  "value": "'.get_var('Percentuale rivalsa INPS').'", "values": "query=SELECT * FROM co_rivalsainps", "required": '.intval($dir != 'uscita').' ]}
        </div>';
    }

    // Ritenuta d'acconto
    if (get_var("Percentuale ritenuta d'acconto") != '' || $dir == 'uscita') {
        echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr("Ritenuta d'acconto").'", "name": "idritenutaacconto", "value": "'.get_var("Percentuale ritenuta d'acconto").'", "values": "query=SELECT * FROM co_ritenutaacconto", "required": '.intval($dir != 'uscita').' ]}
        </div>';
    }

    echo '
    </div>';
}

// Leggo l'iva predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
$iva = $dbo->fetchArray('SELECT idiva_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' AS idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
$idiva = $iva[0]['idiva'] ?: get_var('Iva predefinita');

// Iva
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$idiva.'", "values": "query=SELECT * FROM co_iva ORDER BY descrizione ASC" ]}
        </div>';

echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Conto').'", "name": "idconto", "required": 1, "value": "'.$idconto.'", "ajax-source": "'.$conti.'" ]}
        </div>
    </div>';

// Quantità
echo '
    <div class="row">
        <div class="col-md-2">
            {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "required": 1, "value": "1", "decimals": "qta" ]}
        </div>';

// Unità di misura
echo '
        <div class="col-md-3">
            {[ "type": "select", "label": "'.tr('Unità di misura').'", "icon-after": "add|'.Modules::get('Unità di misura')['id'].'", "name": "um", "ajax-source": "misure" ]}
        </div>';

// Costo unitario
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Costo unitario').'", "name": "prezzo", "required": 1, "icon-after": "&euro;" ]}
        </div>';

// Sconto unitario
$rss = $dbo->fetchArray('SELECT prc_guadagno FROM mg_listini WHERE id=(SELECT idlistino_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica).')');
if (!empty($rss)) {
    $sconto = $rss[0]['prc_guadagno'];
    $tipo_sconto = 'PRC';
}

echo '
        <div class="col-md-3">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "value": "'.$sconto.'", "icon-after": "choice|untprc|'.$tipo_sconto.'" ]}
        </div>
    </div>';

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
    </div>
</form>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';
