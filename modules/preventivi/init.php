<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $preventivo = Modules\Preventivi\Preventivo::with('stato')->find($id_record);

    $record = $dbo->fetchOne('SELECT *,
        (SELECT tipo FROM an_anagrafiche WHERE idanagrafica = co_preventivi.idanagrafica) AS tipo_anagrafica,
        (SELECT fatturabile FROM co_statipreventivi WHERE id=idstato) AS fatturabile,
        (SELECT annullato FROM co_statipreventivi WHERE id=idstato) AS annullato,
        (SELECT descrizione FROM co_statipreventivi WHERE id=idstato) AS stato
    FROM co_preventivi
    WHERE id='.prepare($id_record));
}
