<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

$options = [
    'op' => 'add_preventivo',
    'id_importazione' => 'id_preventivo',
    'final_module' => get('documento') == 'fattura' ? 'Fatture di vendita' : 'Ordini cliente',
    'original_module' => $module['name'],
    'sql' => [
        'table' => 'co_preventivi',
        'rows' => 'co_righe_preventivi',
        'id_rows' => 'idpreventivo',
    ],
    'button' => tr('Aggiungi'),
    'dir' => 'entrata',
    'create_document' => true,
];

$result = [
    'id_record' => $id_record,
    'id_documento' => get('iddocumento'),
];

echo App::load('importa.php', $result, $options, true);
