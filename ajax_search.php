<?php

include_once __DIR__.'/core.php';

if (!isset($term)) {
    /*
    == Super search ==
    Ricerca di un termine su tutti i moduli.
    Il risultato è in json
    */

    $term = get('term');
    $term = str_replace('/', '\\/', $term);

    $results = AJAX::search($term);

    echo json_encode($results);
}

// Casi particolari
else {
}
