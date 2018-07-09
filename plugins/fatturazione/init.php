<?php

include_once __DIR__.'/../../core.php';

$upload_dir = DOCROOT.'/'.Uploads::getDirectory($id_module, $id_plugin);

try {
    $fattura = new Plugins\Fatturazione\FatturaElettronica($id_record);

    $disabled = false;
    $download = file_exists($upload_dir.'/'.$fattura->getFilename());
} catch (UnexpectedValueException $e) {
    $disabled = true;
    $download = false;
}
