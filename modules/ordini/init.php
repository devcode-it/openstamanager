<?php

if (isset($id_record)) {
    $ordine = Modules\Ordini\Ordine::with('tipo', 'stato')->find($id_record);

    // Aggiornamento stato di questo ordine (?)
    if (!empty(get_stato_ordine($id_record)) && setting('Cambia automaticamente stato ordini fatturati')) {
        $dbo->query('UPDATE or_ordini SET id_stato=(SELECT id FROM or_statiordine WHERE descrizione="'.get_stato_ordine($id_record).'") WHERE id='.prepare($id_record));
    }

    $record = $dbo->fetchOne('SELECT *,
        or_ordini.note,
        or_ordini.idpagamento,
        or_ordini.id AS idordine,
        or_statiordine.descrizione AS `stato`,
        or_tipiordine.descrizione AS `descrizione_tipodoc`,
        (SELECT tipo FROM an_anagrafiche WHERE idanagrafica = or_ordini.idanagrafica) AS tipo_anagrafica,
        (SELECT completato FROM or_statiordine WHERE or_statiordine.id=or_ordini.id_stato) AS flag_completato
    FROM or_ordini LEFT OUTER JOIN or_statiordine ON or_ordini.id_stato=or_statiordine.id
        INNER JOIN an_anagrafiche ON or_ordini.idanagrafica=an_anagrafiche.idanagrafica
        INNER JOIN or_tipiordine ON or_ordini.id_tipo_ordine=or_tipiordine.id
    WHERE or_ordini.id='.prepare($id_record));
}
