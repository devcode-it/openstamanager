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

// Conto dalle impostazioni
if (empty($idconto)) {
    $idconto = ($dir == 'entrata') ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');
}

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
    'action' => 'add',
    'dir' => $dir,
    'conti' => $conti,
    'idanagrafica' => $idanagrafica,
    'show-ritenuta-contributi' => !empty($rs[0]['id_ritenuta_contributi']),
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
    'idconto' => $idconto,
    'ritenuta_contributi' => true,
];

// Leggo l'iva predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
$iva = $dbo->fetchArray('SELECT idiva_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' AS idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
$result['idiva'] = $iva[0]['idiva'] ?: setting('Iva predefinita');

// Aggiunta sconto di default da listino per le vendite
$listino = $dbo->fetchArray('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_listini ON an_anagrafiche.idlistino_'.($dir == 'uscita' ? 'acquisti' : 'vendite').'=mg_listini.id WHERE idanagrafica='.prepare($idanagrafica));

if ($listino[0]['prc_guadagno'] > 0) {
    $result['sconto_unitario'] = $listino[0]['prc_guadagno'];
    $result['tipo_sconto'] = 'PRC';
}

// Leggo la ritenuta d'acconto predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
// id_ritenuta_acconto_vendite oppure id_ritenuta_acconto_acquisti
$ritenuta_acconto = $dbo->fetchOne('SELECT id_ritenuta_acconto_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' AS id_ritenuta_acconto FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
$id_ritenuta_acconto = $ritenuta_acconto['id_ritenuta_acconto'];
if ($dir == 'entrata' && empty($id_ritenuta_acconto)) {
    $id_ritenuta_acconto = setting("Percentuale ritenuta d'acconto");
}
$options['id_ritenuta_acconto_predefined'] = $id_ritenuta_acconto;

// Importazione della gestione dedicata
$file = 'riga';
if (get('is_descrizione') !== null) {
    $file = 'descrizione';

    $options['op'] = 'manage_descrizione';
} elseif (get('is_articolo') !== null) {
    $file = 'articolo';

    $options['op'] = 'manage_articolo';
}

echo App::load($file.'.php', $result, $options);
