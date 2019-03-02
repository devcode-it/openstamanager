<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM `mg_unitamisura` WHERE id='.prepare($id_record));
}
