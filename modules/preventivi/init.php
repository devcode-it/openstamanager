<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $preventivo = Modules\Preventivi\Preventivo::with('stato')->find($id_record);

    $record = $dbo->fetchOne('SELECT *, (SELECT descrizione FROM co_statipreventivi WHERE id=idstato) AS stato FROM co_preventivi WHERE id='.prepare($id_record).Modules::getAdditionalsQuery($id_module));
}
