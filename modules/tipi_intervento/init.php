<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM in_tipiintervento WHERE idtipointervento='.prepare($id_record));
}
