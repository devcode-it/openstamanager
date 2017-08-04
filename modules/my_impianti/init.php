<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT *, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=my_impianti.idanagrafica) AS cliente FROM my_impianti WHERE id='.prepare($id_record).Modules::getAdditionalsQuery($id_module));
}
