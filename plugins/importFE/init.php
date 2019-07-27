<?php

include_once __DIR__.'/../../core.php';

use Plugins\ImportFE\FatturaElettronica;
use Plugins\ImportFE\Interaction;

if (isset($id_record)) {
    $files = Interaction::fileToImport();
    $file = $files[$id_record - 1];

    $filename = $file['name'];

    try {
        $fattura_pa = FatturaElettronica::manage($filename);
        $anagrafica = $fattura_pa->findAnagrafica();

        $record = $file;
    } catch (Exception $e) {
    }
}
