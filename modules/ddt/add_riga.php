<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

// Info documento
$rs = $dbo->fetchArray('SELECT * FROM dt_ddt WHERE id='.prepare($id_record));
$numero = (!empty($rs[0]['numero_esterno'])) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];
$idanagrafica = $rs[0]['idanagrafica'];

if (!empty($get['idriga'])) {
    $button = tr('Modifica');

    $idriga = $get['idriga'];

    // Info riga inserita
    $rsr = $dbo->fetchArray('SELECT * FROM dt_righe_ddt WHERE idddt='.prepare($id_record).' AND id='.prepare($idriga));
    $descrizione = $rsr[0]['descrizione'];
    $qta = $rsr[0]['qta'];
    $um = $rsr[0]['um'];
    $idiva = $rsr[0]['idiva'];
    $subtot = $rsr[0]['subtotale'] / $rsr[0]['qta'];
    $sconto = $rsr[0]['sconto_unitario'];
    $tipo_sconto = $rsr[0]['tipo_sconto'];
} else {
    $button = tr('Aggiungi');

    $descrizione = '';
    $qta = 1;
    $um = '';
    $subtot = 0;
    $sconto = 0;

    // Leggo l'iva predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
    $iva = $dbo->fetchArray('SELECT idiva_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' AS idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
    $idiva = $iva[0]['idiva'] ?: get_var('Iva predefinita');

    // Sconto unitario
    $rss = $dbo->fetchArray('SELECT prc_guadagno FROM mg_listini WHERE id=(SELECT idlistino_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica).')');
    if (!empty($rss)) {
        $sconto = $rss[0]['prc_guadagno'];
        $tipo_sconto = 'PRC';
    }
}

echo '
<p>'.tr('Ddt numero _NUM_', [
    '_NUM_' => $numero,
]).'</p>

<form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="'.(!empty($idriga) ? 'editriga' : 'addriga').'">
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
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "required": 1, "value": '.json_encode($descrizione).' ]}
        </div>
    </div>';

// Quantità
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "required": 1, "value": "'.$qta.'", "decimals": "qta" ]}
        </div>';

// Unità di misura
        echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Unità di misura').'", "icon-after": "add|'.Modules::get('Unità di misura')['id'].'", "name": "um", "value": "'.$um.'", "ajax-source": "misure" ]}
        </div>
    </div>';

// Iva
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "values": "query=SELECT id, descrizione FROM co_iva ORDER BY descrizione ASC", "value": "'.$idiva.'" ]}
        </div>';

// Costo unitario
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Costo unitario').'", "name": "prezzo", "required": 1, "value": "'.$subtot.'", "icon-after": "&euro;" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "value": "'.$sconto.'", "icon-after": "choice|untprc|'.$tipo_sconto.'" ]}
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
