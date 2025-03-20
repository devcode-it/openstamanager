<?php

// File e cartelle deprecate
$files = [
    'templates/bilancio/settings.php',
    'templates/contratti/settings.php',
    'templates/ddt/settings.php',
    'templates/libro_giornale/settings.php',
    'templates/magazzino_cespiti/settings.php',
    'templates/magazzino_inventario/settings.php',
    'templates/partitario_mastrino/settings.php',
    'templates/preventivi/settings.php',
    'templates/prima_nota/settings.php',
    'templates/scadenzario/settings.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
