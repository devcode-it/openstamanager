<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *,
        (SELECT tipo FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica = an_sedi.idanagrafica) AS tipo_anagrafica,
        (SELECT iso2 FROM an_nazioni WHERE id = id_nazione) AS iso2
    FROM an_sedi WHERE id='.prepare($id_record));

    $record['lat'] = floatval($record['lat']);
    $record['lng'] = floatval($record['lng']);
}
