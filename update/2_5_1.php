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
