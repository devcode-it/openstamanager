<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;

$rs = $dbo->fetchArray('SELECT idtipoanagrafica, descrizione FROM an_tipianagrafiche');
foreach ($rs as $riga) {
    ${'id_'.strtolower($riga['descrizione'])} = $riga['idtipoanagrafica'];
}

if (isset($id_record)) {
    $anagrafica = Anagrafica::withTrashed()->find($id_record);

    $record = $dbo->fetchOne('SELECT *,
        (SELECT GROUP_CONCAT(an_tipianagrafiche.idtipoanagrafica) FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica=an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE idanagrafica=an_anagrafiche.idanagrafica) AS idtipianagrafica,
        (SELECT GROUP_CONCAT(idagente) FROM an_anagrafiche_agenti WHERE idanagrafica=an_anagrafiche.idanagrafica) AS idagenti,
        (SELECT GROUP_CONCAT(descrizione) FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica=an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE idanagrafica=an_anagrafiche.idanagrafica) AS tipianagrafica
    FROM an_anagrafiche WHERE idanagrafica='.prepare($id_record));

    // Cast per latitudine e longitudine
    if (!empty($record)) {
        $record['lat'] = floatval($record['lat']);
        $record['lng'] = floatval($record['lng']);
    }

    $tipi_anagrafica = $dbo->fetchArray('SELECT an_tipianagrafiche.idtipoanagrafica FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica=an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE idanagrafica='.prepare($id_record));
    $tipi_anagrafica = array_column($tipi_anagrafica, 'idtipoanagrafica');
}
