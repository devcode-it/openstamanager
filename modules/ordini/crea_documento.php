<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

if (get('documento') == 'fattura') {
    $final_module = $module['name'] == 'Ordini cliente' ? 'Fatture di vendita' : 'Fatture di acquisto';
} else {
    $final_module = $module['name'] == 'Ordini cliente' ? 'Ddt di vendita' : 'Ddt di acquisto';
}

$dir = $module['name'] == 'Ordini cliente' ? 'entrata' : 'uscita';

$options = [
    'op' => 'add_ordine',
    'id_importazione' => 'id_ordine',
    'final_module' => $final_module,
    'original_module' => $module['name'],
    'sql' => [
        'table' => 'or_ordini',
        'rows' => 'or_righe_ordini',
        'id_rows' => 'idordine',
    ],
    'serials' => [
        'id_riga' => 'id_riga_ordine',
        'condition' => '(id_riga_ddt IS NOT NULL OR id_riga_documento IS NOT NULL)',
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
