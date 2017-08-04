<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT *, (SELECT descrizione FROM co_statipreventivi WHERE id=idstato) AS stato FROM co_preventivi WHERE id='.prepare($id_record).Modules::getAdditionalsQuery($id_module));
}
