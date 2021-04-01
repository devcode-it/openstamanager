<?php

use Modules\Fatture\Fattura;
use Modules\Fatture\Gestori\Movimenti as GestoreMovimenti;

// Correzione movimenti contabili automatici per Fatture dalla versione 2.4.17 in poi
$fatture = Fattura::where('created_at', '>', '2020-08-01')
    ->whereHas('stato', function ($query) {
        return $query->whereNotIn('descrizione', ['Bozza', 'Annullata']);
    })
    ->get();

foreach ($fatture as $fattura) {
    $gestore = new GestoreMovimenti($fattura);
    $gestore->registra();
}
