<?php

// File e cartelle deprecate
$files = [
    'templates\fatturato\pdfgen.fatturato.php',
    'templates\fatturato\fatturato_body.html',
    'templates\fatturato\fatturato.html',
    'modules\interventi\widgets\interventi.pianificazionedashboard.interventi.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(DOCROOT.'\\'.$value);
}

delete($files);
