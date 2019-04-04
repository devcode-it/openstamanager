<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $dbo->update('co_staticontratti', [
            'descrizione' => (count($dbo->fetchArray('SELECT descrizione FROM co_staticontratti WHERE descrizione = '.prepare(post('descrizione')))) > 0) ? $dbo->fetchOne('SELECT descrizione FROM co_staticontratti WHERE id ='.$id_record)['descrizione'] : post('descrizione'),
            'icona' => post('icona'),
            'is_completato' => post('is_completato') ?: null,
            'is_fatturabile' => post('is_fatturabile') ?: null,
            'is_pianificabile' => post('is_pianificabile') ?: null,
        ], ['id' => $id_record]);

        flash()->info(tr('Informazioni salvate correttamente.'));

        break;

    case 'add':

        $descrizione = post('descrizione');
        $icona = post('icona');
        $is_completato = post('is_completato') ?: null;
        $is_fatturabile = post('is_fatturabile') ?: null;
        $is_pianificabile = post('is_pianificabile') ?: null;

        //controlla descrizione che non sia duplicata
        if (count($dbo->fetchArray('SELECT descrizione FROM co_staticontratti WHERE descrizione='.prepare($descrizione))) > 0) {
            flash()->error(tr('Stato di contratto già esistente.'));
        } else {
            $query = 'INSERT INTO co_staticontratti(descrizione, icona, is_completato, is_fatturabile, is_pianificabile) VALUES ('.prepare($descrizione).', '.prepare($icona).', '.prepare($is_completato).', '.prepare($is_fatturabile).', '.prepare($is_pianificabile).' )';
            $dbo->query($query);
            $id_record = $dbo->lastInsertedID();
            flash()->info(tr('Nuovo stato contratto aggiunto.'));
        }

        break;

    case 'delete':

        //scelgo se settare come eliminato o cancellare direttamente la riga se non è stato utilizzato nei contratti
        if (count($dbo->fetchArray('SELECT id FROM co_contratti WHERE idstato='.prepare($id_record))) > 0) {
            $query = 'UPDATE co_staticontratti SET deleted_at = NOW() WHERE id='.prepare($id_record);
        } else {
            $query = 'DELETE FROM co_staticontratti WHERE id='.prepare($id_record);
        }

        $dbo->query($query);

        flash()->info(tr('Stato contratto eliminato.'));

        break;
}
