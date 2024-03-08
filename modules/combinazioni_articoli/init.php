<?php

use Modules\CombinazioniArticoli\Combinazione;

include_once __DIR__.'/../../core.php';

if (!empty($id_record)) {
    $combinazione = Combinazione::find($id_record);

    $record = $combinazione->toArray();
}
