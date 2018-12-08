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
];

// Dati della riga
$riga = $dbo->fetchOne('SELECT * FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND id='.prepare(get('idriga')));

<<<<<<< HEAD
$result = $rsr[0];
$result["prezzo_acquisto"] = $rsr[0]['subtotale_acquisto'] / $rsr[0]['qta'];
$result['prezzo'] = $rsr[0]['subtotale'] / $rsr[0]['qta'];
=======
$result = $riga;
$result['prezzo'] = $riga['subtotale'] / $riga['qta'];
>>>>>>> 2ae57384089d87555550bf51f8419fa60ad26f2b

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
