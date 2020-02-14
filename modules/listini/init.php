<?php

include_once __DIR__.'/../../core.php';

use Modules\Listini\Listino;

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM mg_listini WHERE id='.prepare($id_record));

    $listino = Listino::find($id_record);
}
