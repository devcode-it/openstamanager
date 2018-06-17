<?php

include_once __DIR__.'/../../core.php';

$operazione = filter('op');

switch ($operazione) {
    case 'addreferente':
        $dbo->insert('an_referenti', [
            'idanagrafica' => $id_parent,
            'nome' => $post['nome'],
            'mansione' => $post['mansione'],
            'telefono' => $post['telefono'],
            'email' => $post['email'],
            'idsede' => $post['idsede'],
        ]);
        $id_record = $dbo->lastInsertedID();

        $_SESSION['infos'][] = tr('Aggiunto nuovo referente!');

        break;

    case 'updatereferente':
        $dbo->update('an_referenti', [
            'idanagrafica' => $id_parent,
            'nome' => $post['nome'],
            'mansione' => $post['mansione'],
            'telefono' => $post['telefono'],
            'email' => $post['email'],
            'idsede' => $post['idsede'],
        ], ['id' => $id_record]);

        $_SESSION['infos'][] = tr('Salvataggio completato!');

        break;

    case 'deletereferente':
        $dbo->query('DELETE FROM `an_referenti` WHERE `id`='.prepare($id_record));

        $_SESSION['infos'][] = tr('Referente eliminato!');

        break;
}
