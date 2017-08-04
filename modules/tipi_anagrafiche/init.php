<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT * FROM an_tipianagrafiche WHERE idtipoanagrafica='.prepare($id_record));
}
