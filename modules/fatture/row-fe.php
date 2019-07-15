<?php

use Modules\Fatture\Fattura;

include_once __DIR__.'/../../core.php';

$documento = Fattura::find($id_record);

// Impostazioni per la gestione
$options = [
    'op' => 'manage_dati_fe',
    'action' => 'edit',
    'dir' => $documento->direzione,
];

// Dati della riga
$id_riga = get('idriga');
$riga = $documento->getRighe()->find($id_riga);

$result = $riga->toArray();
$result = array_merge($result, $riga->dati_aggiuntivi_fe);

echo App::load('riga_fe.php', $result, $options);
