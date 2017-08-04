<?php

include_once __DIR__.'/../../core.php';

$cmp = \Util\Ini::getList($docroot.'/files/my_impianti/');

if (!empty($id_record) && isset($cmp[$id_record - 1])) {
    $records[0]['nomefile'] = $cmp[$id_record - 1][0];
    $records[0]['contenuto'] = file_get_contents($docroot.'/files/my_impianti/'.$records[0]['nomefile']);
}
