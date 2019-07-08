<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT idanagrafica, ragione_sociale, colore FROM an_anagrafiche WHERE idanagrafica = '.prepare($id_record));

    $tipi_interventi = $dbo->fetchArray('SELECT *, in_tipiintervento.idtipointervento AS id, in_tariffe.idtipointervento AS esiste FROM in_tipiintervento LEFT JOIN in_tariffe ON in_tipiintervento.idtipointervento = in_tariffe.idtipointervento AND in_tariffe.idtecnico = '.prepare($id_record).' ORDER BY descrizione');
}
