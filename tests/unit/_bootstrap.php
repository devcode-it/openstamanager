<?php

use Codeception\Util\Autoload;

// Caricamento delle dipendenze e delle librerie del progetto
$namespaces = require_once __DIR__.'/../../config/namespaces.php';
foreach ($namespaces as $path => $namespace) {
    Autoload::addNamespace($namespace.'\\', __DIR__.'/../../'.$path.'/custom/src');
    Autoload::addNamespace($namespace.'\\', __DIR__.'/../../'.$path.'/src');
}
