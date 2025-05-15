<?php

include __DIR__.'/../config.inc.php';

// Rimozione file e cartelle deprecate
$files = [
    'assets/src/js/wacom/modules/clipper-lib/',
    'assets/src/js/wacom/modules/gl-matrix/',
    'assets/src/js/wacom/modules/js-md5/',
    'assets/src/js/wacom/modules/jszip/',
    'assets/src/js/wacom/modules/node-forge/',
    'assets/src/js/wacom/modules/poly2tri/',
    'assets/src/js/wacom/modules/protobufjs/',
    'assets/src/js/wacom/modules/rbush/',
    'assets/src/js/wacom/modules/sjcl/',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);

$module = Models\Module::where('name', 'Fatture di vendita')->first();
$directory = 'files/fatture/';
$files = glob($directory.'*.{xml,pdf}', GLOB_BRACE);
$new_folder = 'files/'.$module->attachments_directory.'/';
directory($new_folder);

$attachments = database()->fetchArray('SELECT `filename` FROM `zz_files` WHERE `id_module` = '.$module->id);
$attachments_filenames = array_column($attachments, 'filename');

foreach ($files as $file) {
    $filename = basename($file);
    rename($file, $new_folder.$filename);
}
