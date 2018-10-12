<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, (SELECT descrizione FROM co_statipreventivi WHERE id=id_stato) AS stato FROM co_preventivi WHERE id='.prepare($id_record).Modules::getAdditionalsQuery($id_module));
}
