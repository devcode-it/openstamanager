<?php

include_once __DIR__.'/../../core.php';

use Plugins\ImportFE\FatturaElettronica;
use Plugins\ImportFE\Interaction;

if (isset($id_record)) {
    $files = Interaction::getFileList();
    $record = $files[$id_record - 1];

    $has_next = isset($files[$id_record]);

    try {
        $fattura_pa = FatturaElettronica::manage($record['name']);
        $anagrafica = $fattura_pa->findAnagrafica();
    } catch (UnexpectedValueException $e) {
        $imported = true;
    } catch (Exception $e) {
        $error = true;
    }

    if(empty($record)){
        flash()->warning(tr('Nessuna fattura da importare!'));
        
        redirect(ROOTDIR.'/controller.php?id_module='.$id_module);
    }
}
