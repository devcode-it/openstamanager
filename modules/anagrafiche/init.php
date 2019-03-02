<?php

use Modules\Anagrafiche\Anagrafica;

$id_azienda = $dbo->fetchArray("SELECT id FROM an_tipianagrafiche WHERE descrizione='Azienda'")[0]['id'];
$id_cliente = $dbo->fetchArray("SELECT id FROM an_tipianagrafiche WHERE descrizione='Cliente'")[0]['id'];
$id_fornitore = $dbo->fetchArray("SELECT id FROM an_tipianagrafiche WHERE descrizione='Fornitore'")[0]['id'];
$id_tecnico = $dbo->fetchArray("SELECT id FROM an_tipianagrafiche WHERE descrizione='Tecnico'")[0]['id'];

if (isset($id_record)) {
    $anagrafica = Anagrafica::find($id_record);

    $record = $dbo->fetchOne('SELECT *,
        (SELECT GROUP_CONCAT(an_tipianagrafiche.id) FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.id=an_tipianagrafiche_anagrafiche.id_tipo_anagrafica WHERE idanagrafica=an_anagrafiche.idanagrafica) AS idtipianagrafica,
        (SELECT GROUP_CONCAT(idagente) FROM an_anagrafiche_agenti WHERE idanagrafica=an_anagrafiche.idanagrafica) AS idagenti,
        (SELECT GROUP_CONCAT(descrizione) FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.id=an_tipianagrafiche_anagrafiche.id_tipo_anagrafica WHERE idanagrafica=an_anagrafiche.idanagrafica) AS tipianagrafica
    FROM an_anagrafiche
    INNER JOIN `an_sedi` ON `an_sedi`.`id`=`an_anagrafiche`.`id_sede_legale`
    WHERE `an_anagrafiche`.`idanagrafica` = '.prepare($id_record));

    // Cast per latitudine e longitudine
    if (!empty($record)) {
        $record['lat'] = floatval($record['lat']);
        $record['lng'] = floatval($record['lng']);
    }

    $tipi_anagrafica = $dbo->fetchArray('SELECT an_tipianagrafiche.id FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.id=an_tipianagrafiche_anagrafiche.id_tipo_anagrafica WHERE idanagrafica='.prepare($id_record));
    $tipi_anagrafica = array_column($tipi_anagrafica, 'id');
}
