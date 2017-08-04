<?php

include_once __DIR__.'/../../core.php';

$records = $dbo->fetchArray('SELECT in_tariffe.id AS idtariffa, in_tipiintervento.idtipointervento, idtecnico, ragione_sociale, descrizione, in_tariffe.costo_ore, in_tariffe.costo_km, in_tariffe.costo_dirittochiamata, in_tariffe.costo_ore_tecnico, in_tariffe.costo_km_tecnico, in_tariffe.costo_dirittochiamata_tecnico FROM ((in_tariffe INNER JOIN an_anagrafiche ON in_tariffe.idtecnico=an_anagrafiche.idanagrafica) LEFT OUTER JOIN in_tipiintervento ON in_tariffe.idtipointervento=in_tipiintervento.idtipointervento) WHERE in_tariffe.id='.prepare($id_record));

//Se non ci sono record nelle tariffe leggo i dati del tecnico singolarmente e creo l'associazione tecnico-tariffe nel primo submit
if ($records[0]['idtariffa'] != $id_record) {
    $v = explode('|', $id_record);

    $idanagrafica = $v[0];
    $idtipointervento = $v[1];

    $records = $dbo->fetchArray("SELECT in_tipiintervento.idtipointervento, idanagrafica AS idtecnico, ragione_sociale, descrizione, '0,00' AS costo_ore, '0,00' AS costo_km, '0,00' AS costo_dirittochiamata, '0,00' AS costo_ore_tecnico, '0,00' AS costo_km_tecnico, '0,00' AS costo_dirittochiamata_tecnico FROM an_anagrafiche LEFT OUTER JOIN in_tipiintervento ON 1=1 WHERE idanagrafica=".prepare($idanagrafica).' AND idtipointervento='.prepare($idtipointervento));
}
