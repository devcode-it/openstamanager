<?php

use Modules\AttributiCombinazioni\Attributo;

include_once __DIR__.'/../../core.php';

if (!empty($id_record)) {
    $attributo = Attributo::find($id_record);

    $record = $attributo->toArray();
}
