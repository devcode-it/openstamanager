<?php

include_once __DIR__.'/../../core.php';

use Models\PrintTemplate;

if (isset($id_record)) {
    $print = PrintTemplate::find($id_record);
    $record = $print->toArray();
}
