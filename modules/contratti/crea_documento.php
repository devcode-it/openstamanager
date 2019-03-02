<?php

$module = Modules::get($id_module);

$options = [
    'op' => 'add_contratto',
    'id_importazione' => 'id_contratto',
    'final_module' => 'Fatture di vendita',
    'original_module' => $module['name'],
    'sql' => [
        'table' => 'co_contratti',
        'rows' => 'co_righe_contratti',
        'id_rows' => 'idcontratto',
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
