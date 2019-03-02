<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM co_movimenti_modelli WHERE id='.prepare($id_record));
}
