<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT * FROM an_sedi WHERE id='.prepare($id_record));

    $records[0]['lat'] = floatval($records[0]['lat']);
    $records[0]['lng'] = floatval($records[0]['lng']);
}
