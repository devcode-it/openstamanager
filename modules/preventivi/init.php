<?php

if (isset($id_record)) {
    $preventivo = Modules\Preventivi\Preventivo::with('stato')->find($id_record);

    $record = $dbo->fetchOne('SELECT *,
        (SELECT tipo FROM an_anagrafiche WHERE idanagrafica = co_preventivi.idanagrafica) AS tipo_anagrafica,
        (SELECT fatturabile FROM co_statipreventivi WHERE id=id_stato) AS fatturabile,
        (SELECT annullato FROM co_statipreventivi WHERE id=id_stato) AS annullato,
        (SELECT descrizione FROM co_statipreventivi WHERE id=id_stato) AS stato
    FROM co_preventivi
    WHERE id='.prepare($id_record));
}
