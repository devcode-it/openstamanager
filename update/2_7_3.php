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
