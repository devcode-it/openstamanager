<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'add':
        $idmastrino = get_new_idmastrino('co_movimenti_modelli');
        $descrizione = post('descrizione');
        $nome = post('nome');

        for ($i = 0; $i < sizeof(post('idconto')); ++$i) {
            $idconto = post('idconto')[$i];
            if(!empty($idconto)){
                $query = 'INSERT INTO co_movimenti_modelli(idmastrino, nome, descrizione, idconto) VALUES('.prepare($idmastrino).', '.prepare($nome).', '.prepare($descrizione).', '.prepare($idconto).')';
                if ($dbo->query($query)) {
                    $id_record = $idmastrino;
                }
            }
        }

        break;

    case 'editriga':
        $idmastrino = post('idmastrino');
        $descrizione = post('descrizione');
        $nome = post('nome');

        // Eliminazione prima nota
        $dbo->query('DELETE FROM co_movimenti_modelli WHERE idmastrino='.prepare($idmastrino));

        for ($i = 0; $i < sizeof(post('idconto')); ++$i) {
            $idconto = post('idconto')[$i];
            if(!empty($idconto)){
            $query = 'INSERT INTO co_movimenti_modelli(idmastrino, nome, descrizione, idconto) VALUES('.prepare($idmastrino).', '.prepare($nome).', '.prepare($descrizione).', '.prepare($idconto).')';
                if ($dbo->query($query)) {
                    $id_record = $idmastrino;
                }
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
