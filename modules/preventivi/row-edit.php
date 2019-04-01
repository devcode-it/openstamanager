<?php

use Modules\Preventivi\Preventivo;

include_once __DIR__.'/../../core.php';

// Info contratto
$documento = Preventivo::find($id_record);

// Impostazioni per la gestione
$options = [
    'op' => 'editriga',
    'action' => 'edit',
    'dir' => $documento->direzione,
    'idanagrafica' => $documento['idanagrafica'],
    'totale' => $documento->totale,
];

// Dati della riga
$id_riga = get('idriga');
$riga = $documento->getRighe()->find($id_riga);

$result = $riga->toArray();
$result['prezzo'] = $riga->prezzo_unitario_vendita;

// Importazione della gestione dedicata

// Importazione della gestione dedicata
$file = 'riga';
if ($riga->isDescrizione()) {
    $file = 'descrizione';
} elseif ($riga->isArticolo()) {
    $file = 'articolo';
} elseif ($riga->isSconto()) {
    $file = 'sconto';

    $options['op'] = 'manage_sconto';
}

echo App::load($file.'.php', $result, $options);
