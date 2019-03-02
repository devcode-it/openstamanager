<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM `dt_aspettobeni` WHERE id='.prepare($id_record));
}
