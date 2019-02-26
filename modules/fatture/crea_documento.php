<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

$options = [
    'op' => 'nota_credito',
    'id_importazione' => 'id_documento',
    'final_module' => $module['name'],
    'original_module' => $module['name'],
    'sql' => [
        'table' => 'co_documenti',
        'rows' => 'co_righe_documenti',
        'id_rows' => 'iddocumento',
    ],
    'serials' => [
        'id_riga' => 'id_riga_documento',
        'condition' => '(1 = 2)',
    ],
    'button' => tr('Aggiungi'),
    'dir' => $dir,
    'create_document' => true,
    'allow-empty' => true,
];

$result = [
    'id_record' => $id_record,
    'id_documento' => get('iddocumento'),
];

echo App::load('importa.php', $result, $options, true);
