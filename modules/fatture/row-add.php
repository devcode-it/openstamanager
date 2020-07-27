<?php

use Modules\Fatture\Fattura;

include_once __DIR__.'/../../core.php';

$documento = Fattura::find($id_record);
$dir = $documento->direzione;

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
    'action' => 'add',
    'dir' => $documento->direzione,
    'conti' => $documento->direzione == 'entrata' ? 'conti-vendite' : 'conti-acquisti',
    'idanagrafica' => $documento['idanagrafica'],
    'show-ritenuta-contributi' => !empty($documento['id_ritenuta_contributi']),
    'totale_imponibile' => $documento->totale_imponibile,
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
$iva = $dbo->fetchArray('SELECT idiva_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' AS idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($documento['idanagrafica']));
$result['idiva'] = $iva[0]['idiva'] ?: setting('Iva predefinita');

if (!empty($documento->dichiarazione)) {
    $result['idiva'] = setting("Iva per lettere d'intento");
}

// Leggo la ritenuta d'acconto predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
// id_ritenuta_acconto_vendite oppure id_ritenuta_acconto_acquisti
$ritenuta_acconto = $dbo->fetchOne('SELECT id_ritenuta_acconto_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' AS id_ritenuta_acconto FROM an_anagrafiche WHERE idanagrafica='.prepare($documento['idanagrafica']));
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

    // Aggiunta sconto di default da listino per le vendite
    $listino = $dbo->fetchOne('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_listini ON an_anagrafiche.idlistino_vendite=mg_listini.id WHERE idanagrafica='.prepare($documento['idanagrafica']));

    if (!empty($listino['prc_guadagno'])) {
        $result['sconto_percentuale'] = $listino['prc_guadagno'];
        $result['tipo_sconto'] = 'PRC';
    }

    $options['op'] = 'manage_articolo';
} elseif (get('is_sconto') !== null) {
    $file = 'sconto';

    $options['op'] = 'manage_sconto';
} elseif (get('is_barcode') !== null) {
    $file = 'barcode';

    $options['op'] = 'manage_barcode';
}

echo App::load($file.'.php', $result, $options);
