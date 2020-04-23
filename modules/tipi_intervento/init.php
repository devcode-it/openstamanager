<?php

include_once __DIR__.'/../../core.php';
use Modules\TipiIntervento\Tipo;

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM in_tipiintervento WHERE idtipointervento='.prepare($id_record));

    $tipo = Tipo::find($id_record);
}
