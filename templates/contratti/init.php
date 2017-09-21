<?php

include_once __DIR__.'/../../core.php';

$module_name = 'Contratti';

// Lettura info fattura
$records = $dbo->fetchArray('SELECT *, (SELECT orario_inizio FROM in_interventi_tecnici WHERE idintervento=in_interventi.id LIMIT 0,1) AS data, co_contratti.descrizione AS `cdescrizione`, co_contratti.idanagrafica AS `idanagrafica`, co_contratti.costo_orario AS costo_orario , co_contratti.costo_km AS costo_km FROM co_contratti LEFT OUTER JOIN (co_righe_contratti LEFT OUTER JOIN in_interventi ON co_righe_contratti.idintervento=in_interventi.id) ON co_contratti.id=co_righe_contratti.idcontratto WHERE co_contratti.id='.prepare($id_record));

$id_cliente = $records[0]['idanagrafica'];
$id_sede = $records[0]['idsede'];
