<?php

try {
    $fattura_pa = new Plugins\ExportPA\FatturaElettronica($id_record);
} catch (UnexpectedValueException $e) {
}

$upload_dir = DOCROOT.'/'.Uploads::getDirectory($id_module, $id_plugin);
