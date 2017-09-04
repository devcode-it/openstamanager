<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $nome = post('nome');
        $prc_guadagno = post('prc_guadagno');
        $note = post('note');

        $query = 'UPDATE mg_listini SET nome='.prepare($nome).', prc_guadagno='.prepare($prc_guadagno).', note='.prepare($note).' WHERE id='.prepare($id_record);
        $dbo->query($query);

        $_SESSION['infos'][] = tr('Informazioni salvate correttamente!');
        break;

    case 'add':
        $nome = post('nome');
        $prc_guadagno = post('prc_guadagno');

        if (isset($nome)) {
            $dbo->query('INSERT INTO mg_listini( nome, prc_guadagno ) VALUES ('.prepare($nome).', '.prepare($prc_guadagno).')');
            $id_record = $dbo->lastInsertedID();

            $_SESSION['infos'][] = tr('Nuovo listino aggiunto!');
        }
        break;

    case 'delete':
        $dbo->query('DELETE FROM mg_listini WHERE id='.prepare($id_record));
        $_SESSION['infos'][] = tr('Listino eliminato!');
        break;
}
