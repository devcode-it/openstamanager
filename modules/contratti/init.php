<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT *, (SELECT descrizione FROM co_staticontratti WHERE id=idstato) AS stato, (SELECT GROUP_CONCAT(my_impianti_contratti.idimpianto) FROM my_impianti_contratti WHERE idcontratto = co_contratti.id) AS matricoleimpianti FROM co_contratti WHERE id='.prepare($id_record).Modules::getAdditionalsQuery($id_module));
}
