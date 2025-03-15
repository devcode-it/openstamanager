<?php

include __DIR__.'/../config.inc.php';
$module = \Models\Module::where('name', 'Fatture di vendita')->first();

$directory = 'files/fatture/';
$files = glob($directory.'*.xml');

$new_folder = 'files/'.$module->attachments_directory.'/';
directory($new_folder);

$attachments = database()->fetchArray('SELECT `filename` FROM `zz_files` WHERE `name` = "Fattura Elettronica" AND `id_module` = '.$module->id);

$attachments_filenames = array_column($attachments, 'filename');

foreach ($files as $file) {
    $filename = basename($file);
    if (in_array($filename, $attachments_filenames)) {
        rename($file, $new_folder.$filename);
    }
}


// File e cartelle deprecate
$files = [
    'assets/src/js/wacom/modules/protobufjs/bin/',
    'assets/src/js/wacom/modules/protobufjs/cli/',
    'assets/src/js/wacom/modules/protobufjs/CHANGELOG.md',
    'assets/src/js/wacom/modules/protobufjs/scripts/changelog.js',
    'assets/src/js/wacom/modules/protobufjs/dist/minimal/README.md',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);