<?php

include_once __DIR__.'/../../core.php';

$idriga = get('idriga');

// Info preventivo
$rs = $dbo->fetchArray('SELECT * FROM co_preventivi WHERE id='.prepare($id_record));
$numero = $rs[0]['numero'];
$idanagrafica = $rs[0]['idanagrafica'];

if (empty($idriga)) {
    $op = 'addriga';
    $button = tr('Aggiungi');

    // valori default
    $idarticolo = '';
    $descrizione = '';
    $qta = 1;
    $um = '';
    $subtot = 0;
    $sconto = 0;

    // Leggo l'iva predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
    $iva = $dbo->fetchArray('SELECT idiva_vendite AS idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
    $idiva = $iva[0]['idiva'] ?: get_var('Iva predefinita');

    // Sconto unitario
    $rss = $dbo->fetchArray('SELECT prc_guadagno FROM mg_listini WHERE id=(SELECT idlistino_vendite FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica).')');
    if (!empty($rss)) {
        $sconto = $rss[0]['prc_guadagno'];
        $tipo_sconto = 'PRC';
    }
} else {
    $op = 'editriga';
    $button = tr('Modifica');

    // carico record da modificare
    $q = 'SELECT * FROM co_righe_preventivi WHERE idpreventivo='.prepare($id_record).' AND id='.prepare($idriga);
    $rsr = $dbo->fetchArray($q);

    $idarticolo = !empty($rsr[0]['idarticolo']) ? $rsr[0]['idarticolo'] : '';
    $descrizione = $rsr[0]['descrizione'];
    $qta = $rsr[0]['qta'];
    $um = $rsr[0]['um'];
    $idiva = $rsr[0]['idiva'];
    $subtot = $rsr[0]['subtotale'] / $rsr[0]['qta'];
    $sconto = $rsr[0]['sconto_unitario'];
    $tipo_sconto = $rsr[0]['tipo_sconto'];
}

/*
    Form add / edit
*/
echo '
<p>'.tr('Preventivo numero _NUM_', [
    '_NUM_' => $numero,
]).'</p>
<form id="form" action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="idriga" value="'.$idriga.'">
    <input type="hidden" name="backto" value="record-edit">';

// Elenco articoli raggruppati per gruppi e sottogruppi
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Articolo').'", "name": "idarticolo", "value": "'.$idarticolo.'", "ajax-source": "articoli", "extra": "onchange=\"session_set(\'superselect,idarticolo\', $(this).val(), 0); $data = $(this).selectData(); $(\'#prezzo\').val($data.prezzo_vendita); $(\'#desc\').val($data.descrizione); $(\'#um\').selectSetNew($data.um, $data.um);\"" ]}
        </div>
    </div>';

// Descrizione
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "id": "desc", "value": '.json_encode($descrizione).', "required": 1 ]}
        </div>
    </div>';

// Quantità
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "value": "'.$qta.'", "required": 1, "decimals": "qta" ]}
        </div>';

// Unità di misura
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Unità di misura').'", "icon-after": "add|'.Modules::get('Unità di misura')['id'].'", "name": "um", "value": "'.$um.'", "ajax-source": "misure" ]}
        </div>';

// Iva
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$idiva.'", "values": "query=SELECT * FROM co_iva ORDER BY descrizione ASC" ]}
        </div>
    </div>';

// Costo unitario
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "number", "label": "'.tr('Costo unitario').'", "name": "prezzo", "required": 1, "value": "'.$subtot.'", "icon-after": "&euro;" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-6">
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
