<?php

// File e cartelle deprecate
$files = [
    'modules/contratti/modutil.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
