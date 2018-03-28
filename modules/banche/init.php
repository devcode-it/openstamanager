<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT * FROM `co_banche` WHERE id='.prepare($id_record));
}
