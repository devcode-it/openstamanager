<?php

use Modules\DDT\DDT;

include_once __DIR__.'/../../core.php';

// Info contratto
$documento = DDT::find($id_record);
$dir = $documento->direzione;

// Impostazioni per la gestione
$options = [
    'op' => 'addriga',
    'action' => 'add',
    'dir' => $documento->direzione,
    'idanagrafica' => $documento['idanagrafica'],
    'imponibile_scontato' => $documento->imponibile_scontato,
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
$iva = $dbo->fetchArray('SELECT idiva_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' AS idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($documento['idanagrafica']));
$result['idiva'] = $iva[0]['idiva'] ?: setting('Iva predefinita');

// Importazione della gestione dedicata
$file = 'riga';
if (get('is_descrizione') !== null) {
    $file = 'descrizione';
} elseif (get('is_articolo') !== null) {
    $file = 'articolo';

    // Aggiunta sconto di default da listino per le vendite
    $listino = $dbo->fetchOne('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_listini ON an_anagrafiche.idlistino_vendite=mg_listini.id WHERE idanagrafica='.prepare($documento['idanagrafica']));

    if (!empty($listino['prc_guadagno'])) {
        $result['sconto_unitario'] = $listino['prc_guadagno'];
        $result['tipo_sconto'] = 'PRC';
    }

    $options['op'] = 'addarticolo';
} elseif (get('is_sconto') !== null) {
    $file = 'sconto';

    $options['op'] = 'manage_sconto';
}

echo App::load($file.'.php', $result, $options);
