<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM in_statiintervento WHERE id='.prepare($id_record));
}
