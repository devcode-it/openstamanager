<?php

include_once __DIR__.'/core.php';

$filename = !empty($filename) ? $filename : null;
$id_print = get('id_print');

// Retrocompatibilitaà
$ptype = get('ptype');
if (!empty($ptype)) {
    $print = $dbo->fetchArray('SELECT id, previous FROM zz_prints WHERE directory = '.prepare($ptype).' ORDER BY main DESC LIMIT 1');
    $id_print = $print[0]['id'];

    $id_record = !empty($id_record) ? $id_record : get($print[0]['previous']);
}

Prints::render($id_print, $id_record, $filename);
