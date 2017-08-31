<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT *, co_documenti.idagente AS idagente_fattura, co_documenti.note, co_documenti.note_aggiuntive, co_documenti.idpagamento, co_documenti.id AS iddocumento, co_statidocumento.descrizione AS `stato`, co_tipidocumento.descrizione AS `descrizione_tipodoc`, (SELECT descrizione FROM co_ritenutaacconto WHERE id=idritenutaacconto) AS ritenutaacconto_desc, (SELECT descrizione FROM co_rivalsainps WHERE id=idrivalsainps) AS rivalsainps_desc FROM ((co_documenti LEFT OUTER JOIN co_statidocumento ON co_documenti.idstatodocumento=co_statidocumento.id) INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE  co_documenti.id='.prepare($id_record));
}
