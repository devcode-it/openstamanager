<?php

include_once __DIR__.'/../../core.php';

use Modules\PrimaNota\PrimaNota;

if (isset($id_record)) {
    $prima_nota = PrimaNota::find($id_record);

    $record = $dbo->fetchOne('SELECT * FROM co_movimenti WHERE idmastrino='.prepare($id_record));
}
