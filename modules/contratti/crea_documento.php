<?php

include_once __DIR__.'/../../core.php';

use Modules\Contratti\Contratto;

$documento = Contratto::find($id_record);

$options = [
    'op' => 'add_documento',
    'type' => 'contratto',
    'module' => 'Fatture di vendita',
    'button' => tr('Aggiungi'),
    'create_document' => true,
    'documento' => $documento,
];

echo App::load('importa.php', [], $options, true);
