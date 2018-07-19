<?php

include_once __DIR__.'/../../core.php';

include_once Modules::filepath('Fatture di vendita', 'modutil.php');

switch (post('op')) {
    case 'add':
        $idmastrino = get_new_idmastrino('co_movimenti_modelli');
        $descrizione = post('descrizione');

        for ($i = 0; $i < sizeof(post('idconto')); ++$i) {
            $idconto = post('idconto')[$i];
            $query = 'INSERT INTO co_movimenti_modelli(idmastrino, descrizione, idconto) VALUES('.prepare($idmastrino).', '.prepare($descrizione).', '.prepare($idconto).')';
            if ($dbo->query($query)) {
                $id_record = $dbo->lastInsertedID();
            }
        }

        break;

    case 'editriga':
        $idmastrino = post('idmastrino');
        $descrizione = post('descrizione');

        // Eliminazione prima nota
        $dbo->query('DELETE FROM co_movimenti_modelli WHERE idmastrino='.prepare($idmastrino));

        for ($i = 0; $i < sizeof(post('idconto')); ++$i) {
            $idconto = post('idconto')[$i];
            $query = 'INSERT INTO co_movimenti_modelli(idmastrino, descrizione, idconto) VALUES('.prepare($idmastrino).', '.prepare($descrizione).', '.prepare($idconto).')';
            if ($dbo->query($query)) {
                $id_record = $dbo->lastInsertedID();
            }
        }

        break;

    case 'delete':
        $idmastrino = post('idmastrino');

        if (!empty($idmastrino)) {
            // Eliminazione prima nota
            $dbo->query('DELETE FROM co_movimenti_modelli WHERE idmastrino='.prepare($idmastrino));

            flash()->info(tr('Movimento eliminato!'));
        }

        break;
}
