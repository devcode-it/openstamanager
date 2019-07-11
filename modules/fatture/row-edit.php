<?php

use Modules\Fatture\Fattura;

include_once __DIR__.'/../../core.php';

$documento = Fattura::find($id_record);

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
    'action' => 'edit',
    'dir' => $documento->direzione,
    'conti' => $documento->direzione == 'entrata' ? 'conti-vendite' : 'conti-acquisti',
    'idanagrafica' => $documento['idanagrafica'],
    'show-ritenuta-contributi' => !empty($documento['id_ritenuta_contributi']),
    'totale_imponibile' => $documento->totale_imponibile,
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
