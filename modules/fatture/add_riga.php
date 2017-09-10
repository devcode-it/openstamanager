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

if (get_var('Percentuale rivalsa INPS') != '' || get_var("Percentuale ritenuta d'acconto") != '') {
    echo '
    <div class="row">';

    // Rivalsa INPS
    if (get_var('Percentuale rivalsa INPS') != '') {
        echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Rivalsa INPS').'", "name": "idrivalsainps", "required": 1, "value": "'.get_var('Percentuale rivalsa INPS').'", "values": "query=SELECT * FROM co_rivalsainps" ]}
        </div>';
    }

    // Ritenuta d'acconto
    if (get_var("Percentuale ritenuta d'acconto") != '') {
        echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr("Ritenuta d'acconto").'", "name": "idritenutaacconto", "required": 1, "value": "'.get_var("Percentuale ritenuta d'acconto").'", "values": "query=SELECT * FROM co_ritenutaacconto" ]}
        </div>';
    }

    echo '
    </div>';
}

// Nelle fatture di acquisto leggo l'iva di acquisto dal fornitore
if ($dir == 'uscita') {
    $rsf = $dbo->fetchArray('SELECT idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
    $idiva_predefinita = $rsf[0]['idiva'];
}

// Leggo l'iva predefinita dall'articolo e se non c'è leggo quella predefinita generica
$idiva = $idiva ?: get_var('Iva predefinita');

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
            {[ "type": "select", "label": "'.tr('Unità di misura').'", "icon-after": "add|'.Modules::getModule('Unità di misura')['id'].'", "name": "um", "ajax-source": "misure" ]}
        </div>';

// Costo unitario
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Costo unitario').'", "name": "prezzo", "required": 1, "icon-after": "&euro;" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-3">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "icon-after": "choice|untprc" ]}
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
