<?php

use Modules\Interventi\Intervento;

include_once __DIR__.'/../../core.php';

$documento = Intervento::find($id_record);

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
    'action' => 'add',
    'dir' => $documento->direzione,
    'idanagrafica' => $documento['idanagrafica'],
    'totale' => $documento->totale,
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
$iva = $dbo->fetchArray('SELECT idiva_vendite AS idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($documento['idanagrafica']));
$result['idiva'] = $iva[0]['idiva'] ?: setting('Iva predefinita');

// Aggiunta sconto di default da listino per le vendite
$listino = $dbo->fetchArray('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_listini ON an_anagrafiche.idlistino_vendite=mg_listini.id WHERE idanagrafica='.prepare($documento['idanagrafica']));

if ($listino[0]['prc_guadagno'] > 0) {
    $result['sconto_unitario'] = $listino[0]['prc_guadagno'];
    $result['tipo_sconto'] = 'PRC';
}

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
