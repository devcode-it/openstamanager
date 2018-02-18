<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $idtipoanagrafica = post('id_record');
        $descrizione = post('descrizione');

        $block = ['Cliente', 'Tecnico', 'Azienda', 'Fornitore'];
        // Nome accettato

        if (!in_array($descrizione, $block)) {
            $dbo->query('UPDATE an_tipianagrafiche SET descrizione='.prepare($descrizione).' WHERE idtipoanagrafica='.prepare($idtipoanagrafica));
            $_SESSION['infos'][] = tr('Informazioni salvate correttamente!');
        } else {
            // Nome non consentito
            $_SESSION['errors'][] = tr('Nome non consentito!');
        }

        break;

    case 'add':
        $descrizione = post('descrizione');

        if (!empty($descrizione)) {
            // Verifico che il nome non sia duplicato
            $rs = $dbo->fetchArray('SELECT descrizione FROM an_tipianagrafiche WHERE descrizione='.prepare($descrizione));

            if (count($rs) > 0) {
                $_SESSION['errors'][] = tr('Nome già esistente!');
            } else {
                $query = 'INSERT INTO an_tipianagrafiche (descrizione) VALUES ('.prepare($descrizione).')';
                $dbo->query($query);

                $id_record = $dbo->lastInsertedID();
                $_SESSION['infos'][] = tr('Nuovo tipo di anagrafica aggiunto!');
            }
        }
        break;

    case 'delete':
        $query = 'DELETE FROM an_tipianagrafiche WHERE idtipoanagrafica='.prepare($id_record);
        $dbo->query($query);

        $_SESSION['infos'][] = tr('Tipo di anagrafica eliminato!');
        break;
}
