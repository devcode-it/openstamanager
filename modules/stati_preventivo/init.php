<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM co_statipreventivi WHERE id='.prepare($id_record));
}
