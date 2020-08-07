<?php

include_once __DIR__.'/../../core.php';

$modulo_import = Modules::get('Import');
$moduli_disponibili = [
    'Anagrafiche' => \Modules\Anagrafiche\Import\CSV::class,
    'Articoli' => \Modules\Articoli\Import\CSV::class,
];

if (!empty($id_record)) {
    $record = $modulo_import->uploads($id_record)->last();
}
