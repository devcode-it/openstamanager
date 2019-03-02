<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM `dt_porto` WHERE id='.prepare($id_record));
}
