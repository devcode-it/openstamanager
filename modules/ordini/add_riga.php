<?php

include_once __DIR__.'/../../core.php';

$module = Modules::getModule($id_module);

if ($module['name'] == 'Ordini cliente') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

// Info documento
$q = 'SELECT * FROM or_ordini WHERE id='.prepare($id_record);
$rs = $dbo->fetchArray($q);
$numero = (!empty($rs[0]['numero_esterno'])) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];
$idanagrafica = $rs[0]['idanagrafica'];

$idriga = $get['idriga'];

if (empty($idriga)) {
    $op = 'addriga';
    $button = _('Aggiungi');

    // valori default
    $descrizione = '';
    $qta = 1;
    $um = '';
    $prezzo = 0;
    $sconto = 0;

    // Nelle fatture di acquisto leggo l'iva di acquisto dal fornitore
    if ($dir == 'uscita') {
        $rsf = $dbo->fetchArray('SELECT idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
        $idiva = $rsf[0]['idiva'];
    }

    // Leggo l'iva predefinita dall'articolo e se non c'è leggo quella predefinita generica
    $idiva = $idiva ?: get_var('Iva predefinita');
} else {
    $op = 'editriga';
    $button = _('Modifica');

    $rsr = $dbo->fetchArray('SELECT * FROM or_righe_ordini WHERE idordine='.prepare($id_record).' AND id='.prepare($idriga));

    $descrizione = $rsr[0]['descrizione'];
    $qta = $rsr[0]['qta'];
    $um = $rsr[0]['um'];
    $idiva = $rsr[0]['idiva'];
    $prezzo = $rsr[0]['subtotale'] / $rsr[0]['qta'];
    $sconto = $rsr[0]['sconto_unitario'];
    $tipo_sconto = $rsr[0]['tipo_sconto'];
}

/*
    Form di inserimento riga documento
*/
echo '
<p>'.str_replace('_NUM_', $numero, _('Ordine numero _NUM_')).'</p>

<form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="dir" value="'.$dir.'">';

if (!empty($idriga)) {
    echo '
    <input type="hidden" name="idriga" value="'.$idriga.'">';
}

// Descrizione
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'._('Descrizione').'", "name": "descrizione", "required": 1, "value": "'.$descrizione.'" ]}
        </div>
    </div>';

// Iva
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'._('Iva').'", "name": "idiva", "required": 1, "value": "'.$idiva.'", "values": "query=SELECT * FROM co_iva ORDER BY descrizione ASC" ]}
        </div>';

// Quantità
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'._('Q.tà').'", "name": "qta", "required": 1, "value": "'.$qta.'", "decimals": "qta" ]}
        </div>';

// Unità di misura
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'._('Unità di misura').'", "icon-after": "add|'.Modules::getModule('Unità di misura')['id'].'", "name": "um", "ajax-source": "misure", "value": "'.$um.'" ]}
        </div>
    </div>';

// Costo unitario
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "number", "label": "'._('Costo unitario').'", "name": "prezzo", "required": 1, "icon-after": "&euro;", "value": "'.$prezzo.'" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-6">
            {[ "type": "number", "label": "'._('Sconto unitario').'", "name": "sconto", "value": "'.$sconto.'", "icon-after": "choice|untprc|'.$tipo_sconto.'" ]}
        </div>
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
