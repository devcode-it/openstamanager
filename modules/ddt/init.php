<?php

include_once __DIR__.'/../../core.php';

use Modules\DDT\DDT;

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

if (isset($id_record)) {
    $ddt = DDT::with('tipo', 'stato')->find($id_record);

    $record = $dbo->fetchOne('SELECT *, dt_ddt.note, dt_ddt.idpagamento, dt_ddt.id AS idddt, dt_statiddt.descrizione AS `stato`, dt_tipiddt.descrizione AS `descrizione_tipodoc`,
    (SELECT completato FROM dt_statiddt WHERE dt_statiddt.id=dt_ddt.idstatoddt) AS flag_completato
    FROM dt_ddt
    LEFT OUTER JOIN dt_statiddt ON dt_ddt.idstatoddt=dt_statiddt.id
    INNER JOIN an_anagrafiche ON dt_ddt.idanagrafica=an_anagrafiche.idanagrafica
    INNER JOIN dt_tipiddt ON dt_ddt.idtipoddt=dt_tipiddt.id
    WHERE dt_ddt.id='.prepare($id_record));

    if (!empty($record)) {
        $record['idporto'] = $record['idporto'] ?: $dbo->fetchOne('SELECT id FROM dt_porto WHERE predefined = 1')['id'];
        $record['idcausalet'] = $record['idcausalet'] ?: $dbo->fetchOne('SELECT id FROM dt_causalet WHERE predefined = 1')['id'];
        $record['idspedizione'] = $record['idspedizione'] ?: $dbo->fetchOne('SELECT id FROM dt_spedizione WHERE predefined = 1')['id'];
    }

    // Se la sede del ddt non è di mia competenza, blocco il ddt in modifica
    $field_name = ( $dir == 'entrata' ) ? 'idsede_partenza' : 'idsede_destinazione';
    if (!in_array($record[$field_name], $user->idsedi)){
        $record['flag_completato'] = 1;
    }
   
}
