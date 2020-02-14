<?php

include_once __DIR__.'/../../core.php';

use Modules\Fatture\Fattura;

$documento = Fattura::find($id_record);

$options = [
    'type' => 'nota_credito',
    'op' => 'nota_credito',
    'module' => 'Fatture di vendita',
    'documento' => $documento,
    'button' => tr('Aggiungi'),
    'create_document' => true,
    'allow-empty' => true,
];

echo App::load('importa.php', [], $options, true);
