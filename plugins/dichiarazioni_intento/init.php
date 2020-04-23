<?php

use Plugins\DichiarazioniIntento\Dichiarazione;

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $dichiarazione = Dichiarazione::find($id_record);

    $record = $dichiarazione->toArray();
}
