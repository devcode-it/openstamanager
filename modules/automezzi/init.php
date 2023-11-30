<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM an_sedi WHERE an_sedi.id='.prepare($id_record));
}
