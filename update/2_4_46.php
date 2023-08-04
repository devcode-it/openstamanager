<?php

// File e cartelle deprecate
$files = [
    'templates/spesometro',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
