<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM `dt_spedizione` WHERE id='.prepare($id_record));
}
