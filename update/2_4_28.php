<?php

// File e cartelle deprecate
$files = [
    'modules/gestione_componenti',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
