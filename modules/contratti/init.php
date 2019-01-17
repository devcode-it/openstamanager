<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, (SELECT tipo FROM an_anagrafiche WHERE idanagrafica = co_contratti.idanagrafica) AS tipo_anagrafica, (SELECT fatturabile FROM co_staticontratti WHERE id=id_stato) AS fatturabile, (SELECT pianificabile FROM co_staticontratti WHERE id=id_stato) AS pianificabile, (SELECT descrizione FROM co_staticontratti WHERE id=id_stato) AS stato, (SELECT GROUP_CONCAT(my_impianti_contratti.idimpianto) FROM my_impianti_contratti WHERE idcontratto = co_contratti.id) AS idimpianti FROM co_contratti WHERE id='.prepare($id_record).Modules::getAdditionalsQuery($id_module));
}
