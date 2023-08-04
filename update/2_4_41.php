<?php

// File e cartelle deprecate
$files = [
    'include/common/barcode.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
