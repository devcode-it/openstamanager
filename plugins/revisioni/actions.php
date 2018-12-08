<?php

include_once __DIR__.'/../../core.php';

$operazione = filter('op');

switch ($operazione) {
    case 'edit_revision':

        $master_revision = post('master_revision');
        $id_record = post('idrevisione');
        //Tolgo il flag default_revision da tutte le revisioni e dal record_principale
        $dbo->query('UPDATE co_preventivi SET default_revision=0 WHERE master_revision='.prepare($master_revision));
        $dbo->query('UPDATE co_preventivi SET default_revision=1 WHERE id='.prepare($id_record));

        flash()->info(tr('Revisione aggiornata!'));
        break;

    case 'delete_revision':

        $idrevisione = post('idrevisione');
        $dbo->query('DELETE FROM co_preventivi WHERE id='.prepare($idrevisione));

        flash()->info(tr('Revisione eliminata!'));
        break;
}
