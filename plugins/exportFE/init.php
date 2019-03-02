<?php

use Plugins\ExportFE\FatturaElettronica;

try {
    $fattura_pa = new FatturaElettronica($id_record);
} catch (UnexpectedValueException $e) {
}

$upload_dir = DOCROOT.'/'.FatturaElettronica::getDirectory();
