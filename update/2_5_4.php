<?php

include_once __DIR__.'/core.php';

// File e cartelle deprecate
$files = [
    'modules/interventi/src/API/v1/Articoli.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
