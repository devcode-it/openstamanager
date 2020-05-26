<?php

include_once __DIR__.'/../../core.php';

use Modules\Preventivi\Preventivo;

$documento = Preventivo::find($id_record);

if (get('documento') == 'fattura') {
    $final_module = 'Fatture di vendita';
    $op = 'add_documento';
} elseif (get('documento') == 'ordine') {
    $final_module = 'Ordini cliente';
    $op = 'add_preventivo';
} else {
    $final_module = 'Contratti';
    $op = 'add_preventivo';
}

$options = [
    'op' => $op,
    'type' => 'preventivo',
    'module' => $final_module,
    'button' => tr('Aggiungi'),
    'dir' => 'entrata',
    'create_document' => true,
    'documento' => $documento,
];

echo App::load('importa.php', [], $options, true);
