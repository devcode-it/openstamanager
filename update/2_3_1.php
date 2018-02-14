<?php

/*
* Rimozione file e cartelle deprecati
*/

// File e cartelle deprecate
$files = [
    'templates/interventi/pdfgen.interventi.php',
    'templates/ddt/pdfgen.ddt.php',
    'templates/ordini/pdfgen.ordini.php',
    'templates/fatture/fattura_body.html',
    'templates/fatture/fattura.html',
    'templates/contratti/contratto_body.html',
    'templates/contratti/contratto.html',
    'templates/preventivo/preventivo_body.html',
    'templates/preventivo/preventivo.html',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(DOCROOT.'/'.$value);
}

delete($files);
