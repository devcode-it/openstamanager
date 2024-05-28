<?php

include_once __DIR__.'/core.php';

use Modules\PrimaNota\Mastrino;

$mastrini = Mastrino::where('primanota', 1)->where('id_scadenza','!=', null)->groupBy('idmastrino')->get();
foreach ($mastrini as $mastrino) {
    $mastrino->aggiornaScadenzario();
}

