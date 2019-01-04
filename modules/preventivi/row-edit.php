<?php

include_once __DIR__.'/../../core.php';

// Info contratto
$rs = $dbo->fetchArray('SELECT * FROM co_preventivi WHERE id='.prepare($id_record));
$idanagrafica = $rs[0]['idanagrafica'];

// Impostazioni per la gestione
$options = [
    'op' => 'editriga',
    'action' => 'edit',
    'dir' => 'entrata',
    'idanagrafica' => $idanagrafica,
];

// Dati della riga
$rsr = $dbo->fetchArray('SELECT * FROM co_righe_preventivi WHERE idpreventivo='.prepare($id_record).' AND id='.prepare(get('idriga')));

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
