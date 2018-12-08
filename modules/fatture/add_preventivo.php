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

$info = $dbo->fetchOne('SELECT * FROM co_documenti WHERE id='.prepare($id_record));
$numero = ($info['numero_esterno'] != '') ? $info['numero_esterno'] : $info['numero'];
$idanagrafica = $info['idanagrafica'];

$idconto = ($dir == 'entrata') ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');

/*
    Form di inserimento riga documento
*/
echo '
<p>'.tr('Documento numero _NUM_', [
    '_NUM_' => $numero,
]).'</p>

<form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="addpreventivo">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="dir" value="'.$dir.'">';

// Preventivo
$_SESSION['superselect']['stati'] = [
    'Accettato',
    'In lavorazione',
    'In attesa di conferma',
];
$_SESSION['superselect']['non_fatturato'] = 1;

echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Preventivo').'", "name": "idpreventivo", "required": 1, "ajax-source": "preventivi", "extra": "onchange=\"$data = $(this).selectData(); $(\'#descrizione\').val($data.text); $(\'#prezzo\').val($data.totale - $data.sconto);console.log($data.totale)\"" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "checkbox", "label": "'.tr('Importa righe').'", "name": "import", "value": "1", "placeholder": "'.tr('Replica righe del preventivo in fattura').'" ]}
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
$idiva = $idiva ?: setting('Iva predefinita');

// Iva
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$idiva.'", "ajax-source": "iva" ]}
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
