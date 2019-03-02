<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM `dt_causalet` WHERE id='.prepare($id_record));
}
