<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM co_scadenziario WHERE id='.prepare($id_record));
}
