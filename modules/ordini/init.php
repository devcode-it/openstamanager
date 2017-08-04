<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT *, or_ordini.note, or_ordini.idpagamento, or_ordini.id AS idordine, or_statiordine.descrizione AS `stato`, or_tipiordine.descrizione AS `descrizione_tipodoc` FROM ((or_ordini LEFT OUTER JOIN or_statiordine ON or_ordini.idstatoordine=or_statiordine.id) INNER JOIN an_anagrafiche ON or_ordini.idanagrafica=an_anagrafiche.idanagrafica) INNER JOIN or_tipiordine ON or_ordini.idtipoordine=or_tipiordine.id WHERE or_ordini.id='.prepare($id_record));
}
