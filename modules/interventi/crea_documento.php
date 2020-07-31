<?php

include_once __DIR__.'/../../core.php';

use Modules\Interventi\Intervento;

$documento = Intervento::find($id_record);

$module = Modules::get($documento->module);

if (get('documento') == 'fattura') {
    $final_module = 'Fatture di vendita';
    $op = 'add_documento';
} elseif (get('documento') == 'ordine_fornitore') {
    $final_module = 'Ordini fornitore';
    $op = 'add_ordine_cliente';
} elseif (get('documento') == 'ordine') {
    $final_module = 'Ordini cliente';
    $op = 'add_documento';
} else {
    $final_module = 'Ddt di vendita';
    $op = 'add_documento';
}

$options = [
    'op' => $op,
    'type' => 'ordine',
    'module' => $final_module,
    'button' => tr('Aggiungi'),
    'create_document' => true,
    'serials' => true,
    'documento' => $documento,
];

echo App::load('importa.php', [], $options, true);
