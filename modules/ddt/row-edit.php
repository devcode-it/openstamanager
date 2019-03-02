<?php

// Info contratto
$rs = $dbo->fetchArray('SELECT * FROM dt_ddt WHERE id='.prepare($id_record));
$idanagrafica = $rs[0]['idanagrafica'];

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

// Impostazioni per la gestione
$options = [
    'op' => 'editriga',
    'action' => 'edit',
    'dir' => $dir,
    'idanagrafica' => $idanagrafica,
    'edit_articolo' => false,
];

// Dati della riga
$rsr = $dbo->fetchArray('SELECT * FROM dt_righe_ddt WHERE idddt='.prepare($id_record).' AND id='.prepare(get('idriga')));

$result = $rsr[0];
$result['prezzo'] = $rsr[0]['subtotale'] / $rsr[0]['qta'];

// Importazione della gestione dedicata
$file = 'riga';
if (!empty($result['is_descrizione'])) {
    $file = 'descrizione';
} elseif (!empty($result['idarticolo'])) {
    $file = 'articolo';
}

echo App::load($file.'.php', $result, $options);
