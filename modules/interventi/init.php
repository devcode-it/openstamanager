<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, (SELECT tipo FROM an_anagrafiche WHERE idanagrafica = in_interventi.idanagrafica) AS tipo_anagrafica, (SELECT completato FROM in_statiintervento WHERE idstatointervento=in_interventi.idstatointervento) AS flag_completato, IF((in_interventi.idsede = 0), (SELECT idzona FROM an_anagrafiche WHERE idanagrafica = in_interventi.idanagrafica), (SELECT idzona FROM an_sedi WHERE id = in_interventi.idsede)) AS idzona, (SELECT colore FROM in_statiintervento WHERE idstatointervento=in_interventi.idstatointervento) AS colore, (SELECT idcontratto FROM co_promemoria WHERE idintervento=in_interventi.id LIMIT 0,1) AS idcontratto, in_interventi.id_preventivo as idpreventivo FROM in_interventi WHERE id='.prepare($id_record).Modules::getAdditionalsQuery($id_module));
}
