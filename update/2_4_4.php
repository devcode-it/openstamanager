<?php

// File e cartelle deprecate
$files = [
    'modules/backup',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(DOCROOT.'/'.$value);
}

delete($files);
