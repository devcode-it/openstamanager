<?php

include_once __DIR__.'/../../core.php';

use Modules\PrimaNota\Mastrino;

if (isset($id_record)) {
    $mastrino = Mastrino::find($id_record);

    $record = $dbo->fetchOne('SELECT * FROM co_movimenti WHERE idmastrino='.prepare($id_record));
}
