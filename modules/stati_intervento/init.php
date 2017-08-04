<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT * FROM in_statiintervento WHERE idstatointervento='.prepare($id_record));
}
