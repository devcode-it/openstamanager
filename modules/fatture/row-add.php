<?php

use Modules\Fatture\Fattura;

include_once __DIR__.'/../../core.php';

$documento = Fattura::find($id_record);

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
    'action' => 'add',
    'dir' => $documento->direzione,
    'conti' => $documento->direzione == 'entrata' ? 'conti-vendite' : 'conti-acquisti',    'idanagrafica' => $documento['idanagrafica'],
    'show-ritenuta-contributi' => !empty($documento['id_ritenuta_contributi']),
    'imponibile_scontato' => $documento->imponibile_scontato,
    'totale' => $documento->totale,
];

// Conto dalle impostazioni
if (empty($idconto)) {
    $idconto = ($dir == 'entrata') ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');
}

// Dati di default
$result = [
    'descrizione' => '',
    'qta' => 1,
    'um' => '',
    'prezzo' => 0,
    'prezzo_acquisto' => 0,
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
$listino = $dbo->fetchArray('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_listini ON an_anagrafiche.idlistino_'.($dir == 'uscita' ? 'acquisti' : 'vendite').'=mg_listini.id WHERE idanagrafica='.prepare($documento['idanagrafica']));

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
} elseif (get('is_sconto') !== null) {
    $file = 'sconto';

    $options['op'] = 'manage_sconto';
}

echo App::load($file.'.php', $result, $options);
