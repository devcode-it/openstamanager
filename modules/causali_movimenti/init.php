<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM `mg_causali_movimenti` WHERE id='.prepare($id_record));
}
