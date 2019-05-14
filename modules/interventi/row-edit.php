<?php

use Modules\Interventi\Intervento;

include_once __DIR__.'/../../core.php';

$documento = Intervento::find($id_record);

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
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
$file = 'riga';
if ($riga->isDescrizione()) {
    $file = 'descrizione';

    $options['op'] = 'manage_descrizione';
} elseif ($riga->isArticolo()) {
    $file = 'articolo';

    $options['op'] = 'manage_articolo';
} elseif ($riga->isSconto()) {
    $file = 'sconto';

    $options['op'] = 'manage_sconto';
}

echo App::load($file.'.php', $result, $options);
