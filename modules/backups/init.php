<?php

include_once __DIR__.'/../../core.php';

try {
    $backup_dir = Backup::getDirectory();
} catch (UnexpectedValueException $e) {
}
