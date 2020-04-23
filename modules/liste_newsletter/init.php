<?php

use Modules\Newsletter\Lista;

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $lista = Lista::find($id_record);

    $record = $lista->toArray();
}
