<?php

// File e cartelle deprecate
$files = [
    'modules/fatture/src/Articolo.php',
    'modules/fatture/src/Riga.php',
    'modules/fatture/src/Descrizione.php',
    'modules/interventi/src/Articolo.php',
    'modules/interventi/src/Riga.php',
    'modules/interventi/src/Descrizione.php',
    'include/src/Article.php',
    'include/src/Row.php',
    'include/src/Description.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(DOCROOT.'/'.$value);
}

delete($files);
