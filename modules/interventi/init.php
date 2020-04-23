<?php

include_once __DIR__.'/../../core.php';

use Modules\Interventi\Intervento;

if (isset($id_record)) {
    $intervento = Intervento::find($id_record);

    $record = $dbo->fetchOne('SELECT *,
       (SELECT tipo FROM an_anagrafiche WHERE idanagrafica = in_interventi.idanagrafica) AS tipo_anagrafica,
       (SELECT is_completato FROM in_statiintervento WHERE idstatointervento=in_interventi.idstatointervento) AS flag_completato,
       IF((in_interventi.idsede_destinazione = 0), (SELECT idzona FROM an_anagrafiche WHERE idanagrafica = in_interventi.idanagrafica), (SELECT idzona FROM an_sedi WHERE id = in_interventi.idsede_destinazione)) AS idzona,
       (SELECT colore FROM in_statiintervento WHERE idstatointervento=in_interventi.idstatointervento) AS colore,
       in_interventi.id_preventivo as idpreventivo,
       in_interventi.id_contratto as idcontratto
    FROM in_interventi WHERE id='.prepare($id_record));
}
