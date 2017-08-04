<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT * FROM in_tipiintervento WHERE idtipointervento='.prepare($id_record));
}
