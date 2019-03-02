<?php

$module_name = 'Contratti';

// Lettura info fattura
$records = $dbo->fetchArray('SELECT *, co_contratti.descrizione AS desc_contratto, (SELECT orario_inizio FROM in_interventi_tecnici WHERE idintervento=in_interventi.id LIMIT 0,1) AS data, (SELECT fatturabile FROM co_staticontratti WHERE id=id_stato) AS fatturabile, (SELECT GROUP_CONCAT(my_impianti_contratti.idimpianto) FROM my_impianti_contratti WHERE idcontratto = co_contratti.id) AS idimpianti, co_contratti.descrizione AS `cdescrizione`, co_contratti.idanagrafica AS `idanagrafica`, co_contratti.costo_orario AS costo_orario , co_contratti.costo_km AS costo_km FROM co_contratti LEFT OUTER JOIN (co_promemoria LEFT OUTER JOIN in_interventi ON co_promemoria.idintervento=in_interventi.id) ON co_contratti.id=co_promemoria.idcontratto WHERE co_contratti.id='.prepare($id_record));

$id_cliente = $records[0]['idanagrafica'];
$id_sede = $records[0]['idsede'];
