<?php

use Modules\FileAdapters\FileAdapter;

include __DIR__.'/core.php';
// Imposto l'adattatore locale di default se non definito
$default = FileAdapter::where('is_default', 1)->first();
if (empty($default)) {
    $adapter = FileAdapter::where('name', 'Adattatore locale')->first();
    $adapter->is_default = 1;
    $adapter->save();
}

// Se non è installato il modulo distinta base elimino il plugin
$fk = $database->fetchArray('SELECT * FROM `zz_plugins` WHERE `directory` = "distinta_base"');
if (empty($fk)) {
    // File e cartelle deprecate
    delete(realpath(base_dir() . '/plugins/distinta_base/'));
}