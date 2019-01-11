<?php

include_once __DIR__.'/../../core.php';

// Info contratto
$rs = $dbo->fetchArray('SELECT * FROM or_ordini WHERE id='.prepare($id_record));
$idanagrafica = $rs[0]['idanagrafica'];

if ($module['name'] == 'Ordini cliente') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

// Impostazioni per la gestione
$options = [
    'op' => 'addriga',
    'action' => 'add',
    'dir' => $dir,
    'idanagrafica' => $idanagrafica,
];

// Dati di default
$result = [
    'descrizione' => '',
    'qta' => 1,
    'um' => '',
    'prezzo' => 0,
    'sconto_unitario' => 0,
    'tipo_sconto' => '',
    'idiva' => '',
];

// Leggo l'iva predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
$iva = $dbo->fetchArray('SELECT idiva_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' AS idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
$result['idiva'] = $iva[0]['idiva'] ?: setting('Iva predefinita');

// Aggiunta sconto di default da listino per le vendite
$listino = $dbo->fetchArray('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_listini ON an_anagrafiche.idlistino_'.($dir == 'uscita' ? 'acquisti' : 'vendite').'=mg_listini.id WHERE idanagrafica='.prepare($idanagrafica));

if( $listino[0]['prc_guadagno'] > 0 ){
    $result['sconto_unitario'] = $listino[0]['prc_guadagno'];
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
