<?php

// File e cartelle deprecate
$files = [
    'modules/contratti/modutil.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(DOCROOT.'/'.$value);
}

delete($files);
