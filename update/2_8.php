<?php

include __DIR__.'/../config.inc.php';

// Spostamento backup
$directory = 'backup/';
$files = glob($directory.'*.{zip}', GLOB_BRACE);
$new_folder = 'files/backups/';
directory($new_folder);

foreach ($files as $file) {
    $filename = basename($file);
    rename($file, $new_folder.$filename);
}

// File e cartelle deprecate
$files = [
    'templates/bilancio/settings.php',
    'templates/contratti/settings.php',
    'templates/ddt/settings.php',
    'templates/libro_giornale/settings.php',
    'templates/magazzino_cespiti/settings.php',
    'templates/magazzino_inventario/settings.php',
    'templates/partitario_mastrino/settings.php',
    'templates/preventivi/settings.php',
    'templates/prima_nota/settings.php',
    'templates/scadenzario/settings.php',
    'backup/',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);


$module = Models\Module::where('name', 'Fatture di acquisto')->first();
$directory = 'files/fatture/vendite/';
$files = glob($directory.'*.{xml,pdf}', GLOB_BRACE);
$new_folder = 'files/'.$module->attachments_directory.'/';
directory($new_folder);

$attachments = database()->fetchArray('SELECT `filename` FROM `zz_files` WHERE `id_module` = '.$module->id);
$attachments_filenames = array_column($attachments, 'filename');

foreach ($files as $file) {
    echo $file;
    $filename = basename($file);
    rename($file, $new_folder.$filename);
}