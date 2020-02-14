<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $descrizione = post('descrizione');
        $categoria = post('categoria');

        $dbo->query('UPDATE in_vociservizio SET descrizione='.prepare($descrizione).', categoria='.prepare($categoria).' WHERE id='.prepare($id_record));

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

    case 'add':
        $descrizione = post('descrizione');
        $categoria = post('categoria');

        $dbo->query('INSERT INTO in_vociservizio(descrizione, categoria) VALUES ('.prepare($descrizione).', '.prepare($categoria).')');
        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Nuova voce di servizio aggiunta!'));

        break;

    case 'delete':
        $dbo->query('DELETE FROM in_vociservizio WHERE id='.prepare($id_record));
        break;
}
