<?php

include_once __DIR__.'/../../core.php';

$upload_dir = DOCROOT.'/'.Uploads::getDirectory($id_module, $id_plugin);

try {
    $fattura = new Plugins\Fatturazione\FatturaElettronica($id_record);

    $disabled = false;
    $download = file_exists($upload_dir.'/'.$fattura->getFilename());
} catch (UnexpectedValueException $e) {
    $disabled = true;
    $download = false;
}

// Campi obbligatori per l'anagrafica Azienda
$azienda = Plugins\Fatturazione\FatturaElettronica::getAzienda();
$fields = [
    'piva' => 'Partita IVA',
    // 'codice_fiscale' => 'Codice Fiscale',
    'citta' => 'Città',
    'indirizzo' => 'Indirizzo',
    'cap' => 'C.A.P.',
    'id_nazione' => 'Nazione',
];

$missing = [];
foreach ($fields as $key => $name) {
    if (empty($azienda[$key])) {
        $missing[] = $name;
    }
}

if (!empty($missing)) {
    echo '
<div class="alert alert-warning">
    <p><i class="fa fa-warning"></i> '.tr("Prima di procedere alla generazione della fattura elettronica completa i seguenti campi dell'anagrafica Azienda: _FIELDS_", [
        '_FIELDS_' => '<b>'.implode(', ', $missing).'</b>',
    ]).'</p>
    <p>'.Modules::link('Anagrafiche', $azienda['idanagrafica'], tr('Vai alla scheda anagrafica'), null).'</p>
</div>';
}

// Campi obbligatori per l'anagrafica Cliente
$cliente = $dbo->fetchOne('SELECT * FROM an_anagrafiche WHERE idanagrafica = '.prepare($records[0]['idanagrafica']));
$fields = [
    // 'piva' => 'Partita IVA',
    // 'codice_fiscale' => 'Codice Fiscale',
    'citta' => 'Città',
    'indirizzo' => 'Indirizzo',
    'cap' => 'C.A.P.',
    'id_nazione' => 'Nazione',
];

$missing = [];
foreach ($fields as $key => $name) {
    if (empty($azienda[$key])) {
        $missing[] = $name;
    }
}

if (!empty($missing)) {
    echo '
<div class="alert alert-warning">
    <p><i class="fa fa-warning"></i> '.tr("Prima di procedere alla generazione della fattura elettronica completa i seguenti campi dell'anagrafica Cliente: _FIELDS_", [
        '_FIELDS_' => '<b>'.implode(', ', $missing).'</b>',
    ]).'</p>
    <p>'.Modules::link('Anagrafiche', $records[0]['idanagrafica'], tr('Vai alla scheda anagrafica'), null).'</p>
</div>';
}

if ($download) {
    echo '
<div class="row">
    <div class="col-md-6">';
}

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">
	<input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="generate">

    <button type="submit" class="btn btn-primary btn-lg btn-block'.($disabled ? ' disabled' : null).'" '.($disabled ? ' disabled' : null).'>
        <i class="fa fa-file"></i> '.tr('Genera fattura elettronica').'
    </button>
</form>';

if ($download) {
    echo '
    </div>

    <div class="col-md-6">
        <a href="'.ROOTDIR.'/editor.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.$id_record.'&op=download" class="btn btn-success btn-lg btn-block" target="_blank">
            <i class="fa fa-download "></i> '.tr('Scarica fattura elettronica').'
        </a>
    </div>
</div>';
}
