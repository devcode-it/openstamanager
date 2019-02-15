<?php

include_once __DIR__.'/../../core.php';

// Info documento
$rs = $dbo->fetchArray('SELECT * FROM co_documenti WHERE id='.prepare($id_record));
$idanagrafica = $rs[0]['idanagrafica'];

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
    $conti = 'conti-vendite';
} else {
    $dir = 'uscita';
    $conti = 'conti-acquisti';
}

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
    'action' => 'edit',
    'dir' => $dir,
    'conti' => $conti,
    'idanagrafica' => $idanagrafica,
    'edit_articolo' => false,
    'show-ritenuta-contributi' => !empty($rs[0]['id_ritenuta_contributi']),
];

// Dati della riga
$riga = $dbo->fetchOne('SELECT * FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND id='.prepare(get('idriga')));

$result = $riga;
$result['prezzo'] = $riga['subtotale'] / $riga['qta'];

// Importazione della gestione dedicata
$file = 'riga';
if (!empty($result['is_descrizione'])) {
    $file = 'descrizione';

    $options['op'] = 'manage_descrizione';
} elseif (!empty($result['idarticolo'])) {
    $file = 'articolo';

    $options['op'] = 'manage_articolo';
}

echo App::load($file.'.php', $result, $options);
