<?php

use Modules\Interventi\Intervento;

include_once __DIR__.'/../../core.php';

$documento = Intervento::find($id_record);
$show_prezzi = Auth::user()['gruppo'] != 'Tecnici' || (Auth::user()['gruppo'] == 'Tecnici' && setting('Mostra i prezzi al tecnico'));

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
    'action' => 'edit',
    'dir' => $documento->direzione,
    'idanagrafica' => $documento['idanagrafica'],
    'totale_imponibile' => $documento->totale_imponibile,
    'nascondi_prezzi' => !$show_prezzi,
];

// Dati della riga
$id_riga = get('riga_id');
$type = get('riga_type');
$riga = $documento->getRiga($type, $id_riga);

$result = $riga->toArray();
$result['prezzo'] = $riga->prezzo_unitario;

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
