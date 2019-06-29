<?php

include_once __DIR__.'/../../core.php';

$operazione = filter('op');

switch ($operazione) {
    case 'addreferente':
        if (!empty(post('nome'))) {
            $dbo->insert('an_referenti', [
                'idanagrafica' => $id_parent,
                'nome' => post('nome'),
                'mansione' => post('mansione'),
                'telefono' => post('telefono'),
                'email' => post('email'),
                'idsede' => post('idsede'),
            ]);
            $id_record = $dbo->lastInsertedID();

            if (isAjaxRequest() && !empty($id_record)) {
                echo json_encode(['id' => $id_record, 'text' => post('nome')]);
            }

            flash()->info(tr('Aggiunto nuovo referente!'));
        } else {
            flash()->warning(tr('Errore durante aggiunta del referente'));
        }

        break;

    case 'updatereferente':
        $dbo->update('an_referenti', [
            'idanagrafica' => $id_parent,
            'nome' => post('nome'),
            'mansione' => post('mansione'),
            'telefono' => post('telefono'),
            'email' => post('email'),
            'idsede' => post('idsede'),
        ], ['id' => $id_record]);

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'deletereferente':
        $dbo->query('DELETE FROM `an_referenti` WHERE `id`='.prepare($id_record));

        flash()->info(tr('Referente eliminato!'));

        break;
}
