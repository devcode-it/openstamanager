<?php

include __DIR__.'/../config.inc.php';

$directory = 'files/fatture/';

$files = glob($directory.'*.');

foreach ($files as $file) {
    $newFilename = str_replace('.', '.xml', $file);

    rename($file, $newFilename);
}
