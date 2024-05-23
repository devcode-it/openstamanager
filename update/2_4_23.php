<?php

use Modules\Banche\Banca;
use Modules\Fatture\Fattura;
use Modules\Fatture\Gestori\Movimenti as GestoreMovimenti;

// Correzione movimenti contabili automatici per Fatture dalla versione 2.4.17 in poi
$fatture = Fattura::where('created_at', '>', '2020-08-01')
    ->whereHas('stato', fn ($query) => $query->whereNotIn('descrizione', ['Bozza', 'Annullata']))
    ->get();

foreach ($fatture as $fattura) {
    $gestore = new GestoreMovimenti($fattura);
    $gestore->registra();
}

// Completamento automatico informazioni IBAN per banche
$banche = Banca::all();
foreach ($banche as $banca) {
    try {
        $banca->save();
    } catch (Exception) {
    }
}
