<?php

include_once __DIR__.'/../../core.php';

// Info documento
$rs = $dbo->fetchArray('SELECT idanagrafica FROM co_documenti WHERE id='.prepare($id_record));
$idanagrafica = $rs[0]['idanagrafica'];

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
    $conti = 'conti-vendite';
} else {
    $dir = 'uscita';
    $conti = 'conti-acquisti';
}

// Conto dalle impostazioni
if (empty($idconto)) {
    $idconto = ($dir == 'entrata') ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');
}

// Impostazioni per la gestione
$options = [
    'op' => 'addriga',
    'action' => 'add',
    'dir' => $dir,
    'conti' => $conti,
    'idanagrafica' => $idanagrafica,
];

$_SESSION['superselect']['dir'] = $dir;

// Dati di default
$result = [
    'descrizione' => '',
    'qta' => 1,
    'um' => '',
    'prezzo' => 0,
    'sconto_unitario' => 0,
    'tipo_sconto' => '',
    'idiva' => '',
    'idconto' => $idconto,
];

// Leggo l'iva predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
$iva = $dbo->fetchArray('SELECT idiva_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' AS idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
$result['idiva'] = $iva[0]['idiva'] ?: setting('Iva predefinita');

// Sconto unitario
$rss = $dbo->fetchArray('SELECT prc_guadagno FROM mg_listini WHERE id=(SELECT idlistino_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica).')');
if (!empty($rss)) {
    $result['sconto_unitario'] = $rss[0]['prc_guadagno'];
    $result['tipo_sconto'] = 'PRC';
}

// Importazione della gestione dedicata
$file = 'riga';
if (get('is_descrizione') !== null) {
    $file = 'descrizione';
} elseif (get('is_articolo') !== null) {
    $file = 'articolo';

    $options['op'] = 'addarticolo';
}

echo App::load($file.'.php', $result, $options);
