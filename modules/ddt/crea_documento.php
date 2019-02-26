<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

$final_module = $module['name'] == 'Ddt di vendita' ? 'Fatture di vendita' : 'Fatture di acquisto';
$dir = $module['name'] == 'Ddt di vendita' ? 'entrata' : 'uscita';

$options = [
    'op' => 'add_ddt',
    'id_importazione' => 'id_ddt',
    'final_module' => $final_module,
    'original_module' => $module['name'],
    'sql' => [
        'table' => 'dt_ddt',
        'rows' => 'dt_righe_ddt',
        'id_rows' => 'idddt',
    ],
    'serials' => [
        'id_riga' => 'id_riga_ddt',
        'condition' => '(id_riga_documento IS NOT NULL)',
    ],
    'button' => tr('Aggiungi'),
    'dir' => $dir,
    'create_document' => true,
];

$result = [
    'id_record' => $id_record,
    'id_documento' => get('iddocumento'),
];

echo App::load('importa.php', $result, $options, true);
