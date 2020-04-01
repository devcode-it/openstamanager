<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $preventivo = Modules\Preventivi\Preventivo::with('stato')->find($id_record);

    $record = $dbo->fetchOne('SELECT co_preventivi.*,
        (SELECT tipo FROM an_anagrafiche WHERE idanagrafica = co_preventivi.idanagrafica) AS tipo_anagrafica,
        co_statipreventivi.is_fatturabile,
        co_statipreventivi.is_completato,
        co_statipreventivi.is_revisionabile,
        co_statipreventivi.descrizione AS stato
    FROM co_preventivi LEFT JOIN co_statipreventivi ON co_preventivi.idstato=co_statipreventivi.id
    WHERE co_preventivi.id='.prepare($id_record));
}
