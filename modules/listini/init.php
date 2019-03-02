<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM mg_listini WHERE id='.prepare($id_record));
}
