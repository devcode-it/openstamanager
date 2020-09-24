<?php

// File e cartelle deprecate
$files = [
    'modules/backup',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
