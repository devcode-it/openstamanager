<?php

// File e cartelle deprecate
$files = [
    'files/impianti/componente.ini',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
