<?php

// File e cartelle deprecate
$files = [
    'modules/interventi/widgets/stampa_riepilogo.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);