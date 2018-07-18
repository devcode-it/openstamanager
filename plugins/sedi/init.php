<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM an_sedi WHERE id='.prepare($id_record));

    $record['lat'] = floatval($record['lat']);
    $record['lng'] = floatval($record['lng']);
}
