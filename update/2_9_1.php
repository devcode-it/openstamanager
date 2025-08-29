<?php

include __DIR__.'/../config.inc.php';

$files = [
    'modules/primanota/movimenti_utils.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
