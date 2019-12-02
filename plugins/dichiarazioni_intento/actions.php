<?php

use Modules\Anagrafiche\Anagrafica;
use Plugins\DichiarazioniIntento\Dichiarazione;

include_once __DIR__.'/../../core.php';

$operazione = filter('op');

switch ($operazione) {
    case 'add':
        $anagrafica = Anagrafica::find($id_parent);
        $dichiarazione = Dichiarazione::build($anagrafica, post('data'), post('numero_protocollo'), post('numero_progressivo'), post('data_inizio'), post('data_fine'));

        $dichiarazione->massimale = post('massimale');
        $dichiarazione->data_protocollo = post('data_protocollo');
        $dichiarazione->data_emissione = post('data_emissione');
        $dichiarazione->save();

         $id_record = $dichiarazione->id;

         if (isAjaxRequest() && !empty($id_record)) {
             echo json_encode(['id' => $id_record, 'text' => $dichiarazione->numero_protocollo.' - '.$dichiarazione->numero_progressivo]);
         }

         flash()->info(tr("Aggiunta una dichiarazione d'intento!"));

        break;

    case 'update':
        $dichiarazione->massimale = post('massimale');
        $dichiarazione->data = post('data');
        $dichiarazione->numero_protocollo = post('numero_protocollo');
        $dichiarazione->numero_progressivo = post('numero_progressivo');
        $dichiarazione->data_inizio = post('data_inizio');
        $dichiarazione->data_fine = post('data_fine');
        $dichiarazione->data_protocollo = post('data_protocollo');
        $dichiarazione->data_emissione = post('data_emissione');
        $dichiarazione->save();

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'delete':
        $dichiarazione->delete();

        flash()->info(tr("Dichiarazione d'intento eliminata!"));

        break;
}
