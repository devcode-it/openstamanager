<?php

$imports = Import::getImports();

if (!empty($id_record)) {
    $records = Import::get($id_record)['files'];
}
