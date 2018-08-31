<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, (SELECT completato FROM in_statiintervento WHERE idstatointervento=in_interventi.idstatointervento) AS flag_completato, IF((in_interventi.idsede = 0), (SELECT idzona FROM an_anagrafiche WHERE idanagrafica = in_interventi.idanagrafica), (SELECT idzona FROM an_sedi WHERE id = in_interventi.idsede)) AS idzona, (SELECT colore FROM in_statiintervento WHERE idstatointervento=in_interventi.idstatointervento) AS colore, (SELECT idcontratto FROM co_promemoria WHERE idintervento=in_interventi.id LIMIT 0,1) AS idcontratto, (SELECT idpreventivo FROM co_preventivi_interventi WHERE idintervento=in_interventi.id LIMIT 0,1) AS idpreventivo FROM in_interventi WHERE id='.prepare($id_record).Modules::getAdditionalsQuery($id_module));
}

$jscript_modules[] = $rootdir.'/modules/interventi/js/interventi_helperjs.js';
