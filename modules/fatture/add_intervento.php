<?php

include_once __DIR__.'/../../core.php';
include_once $docroot.'/modules/interventi/modutil.php';

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
    <input type="hidden" name="op" value="addintervento">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="dir" value="'.$dir.'">';

$rs = $dbo->fetchArray('SELECT in_interventi.id, CONCAT(\'Intervento numero \', codice, \' del \', DATE_FORMAT(IFNULL((SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento=in_interventi.id), data_richiesta), \'%d/%m/%Y\'), \'\n\', descrizione) AS descrizione, IF(idclientefinale='.prepare($idanagrafica).', \'Interventi conto terzi\', \'Interventi diretti\')  AS `optgroup`FROM in_interventi WHERE (idanagrafica='.prepare($idanagrafica).' OR idclientefinale='.prepare($idanagrafica).') AND NOT idstatointervento=\'DENY\' AND in_interventi.id NOT IN (SELECT idintervento FROM co_righe_documenti WHERE idintervento IS NOT NULL) AND NOT in_interventi.id IN (SELECT idintervento FROM co_preventivi_interventi WHERE idintervento IS NOT NULL) AND NOT in_interventi.id IN (SELECT idintervento FROM co_righe_contratti WHERE idintervento IS NOT NULL)');
foreach ($rs as $key => $value) {
    $rs[$key]['prezzo'] = get_costi_intervento($value['id'])['totale'];
}

// Intervento
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Intervento').'", "name": "idintervento", "required": 1, "values": '.json_encode($rs).', "extra": "onchange=\"$data = $(this).selectData(); $(\'#descrizione\').val($data.descrizione); $(\'#prezzo\').val($data.prezzo);\"" ]}
        </div>
    </div>';

// Descrizione
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "required": 1 ]}
        </div>
    </div>';

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

// Costo unitario
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "number", "label": "'.tr('Costo unitario').'", "name": "prezzo", "required": 1, "icon-after": "&euro;", "disabled": 1 ]}
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
