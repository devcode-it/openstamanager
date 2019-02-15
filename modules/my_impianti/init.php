<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=my_impianti.idanagrafica) AS cliente FROM my_impianti WHERE id='.prepare($id_record));
}
