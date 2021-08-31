<?php

use Modules\CombinazioniArticoli\Combinazione;

include_once __DIR__.'/../../core.php';

if (!empty($id_record)) {
    $combinazione = Combinazione::withTrashed()->find($id_record);

    $record = $combinazione->toArray();
}
