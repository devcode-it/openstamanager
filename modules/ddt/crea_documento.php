<?php

include_once __DIR__.'/../../core.php';

use Modules\DDT\DDT;

$documento = DDT::find($id_record);

$module = Modules::get($id_module);

$final_module = $module['name'] == 'Ddt di vendita' ? 'Fatture di vendita' : 'Fatture di acquisto';
$dir = $module['name'] == 'Ddt di vendita' ? 'entrata' : 'uscita';

$options = [
    'op' => 'add_documento',
    'type' => 'ddt',
    'module' => $final_module,
    'serials' => true,
    'button' => tr('Aggiungi'),
    'dir' => $dir,
    'create_document' => true,
    'documento' => $documento,
];

echo App::load('importa.php', [], $options, true);
