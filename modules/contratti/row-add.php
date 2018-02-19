<?php

include_once __DIR__.'/../../core.php';

// Info contratto
$rs = $dbo->fetchArray('SELECT * FROM co_contratti WHERE id='.prepare($id_record));
$idanagrafica = $rs[0]['idanagrafica'];

// Impostazioni per la gestione
$options = [
    'op' => 'addriga',
    'button' => tr('Aggiungi'),
    'dir' => 'entrata',
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
$iva = $dbo->fetchArray('SELECT idiva_vendite AS idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
$result['idiva'] = $iva[0]['idiva'] ?: get_var('Iva predefinita');

// Sconto unitario
$rss = $dbo->fetchArray('SELECT prc_guadagno FROM mg_listini WHERE id=(SELECT idlistino_vendite FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica).')');
if (!empty($rss)) {
    $result['sconto_unitario'] = $rss[0]['prc_guadagno'];
    $result['tipo_sconto'] = 'PRC';
}

// Importazione della gestione dedicata
$file = 'riga';
if (isset($get['is_descrizione'])) {
    $file = 'descrizione';
} elseif (isset($get['is_articolo'])) {
    $file = 'articolo';
}

echo App::load($file.'.php', $result, $options);
