<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $dbo->update('in_statiintervento', [
            'codice' => post('codice'),
            'descrizione' => post('descrizione'),
            'colore' => post('colore'),
            'completato' => post('completato'),
            'notifica' => post('notifica'),
            'id_email' => post('email') ?: null,
            'destinatari' => post('destinatari'),
        ], ['idstatointervento' => $id_record]);

        flash()->info(tr('Informazioni salvate correttamente.'));

        break;

    case 'add':
        $codice = post('codice');
        $descrizione = post('descrizione');
        $colore = post('colore');

        //controllo che il codice non sia duplicato
        if (count($dbo->fetchArray('SELECT idstatointervento FROM in_statiintervento WHERE codice='.prepare($codice))) > 0) {
            flash()->warning(tr('Attenzione: lo stato attività _COD_ risulta già esistente.', [
                '_COD_' => $codice,
            ]));
        } else {
            $query = 'INSERT INTO in_statiintervento(codice, descrizione, colore) VALUES ('.prepare($codice).', '.prepare($descrizione).', '.prepare($colore).')';
            $dbo->query($query);
            $id_record = $database->lastInsertedID();
            flash()->info(tr('Nuovo stato attività aggiunto.'));
        }

        break;

    case 'delete':

        //scelgo se settare come eliminato o cancellare direttamente la riga se non è stato utilizzato negli interventi
        if (count($dbo->fetchArray('SELECT id FROM in_interventi WHERE idstatointervento='.prepare($id_record))) > 0) {
            $query = 'UPDATE in_statiintervento SET deleted_at = NOW() WHERE idstatointervento='.prepare($id_record).' AND `can_delete`=1';
        } else {
            $query = 'DELETE FROM in_statiintervento  WHERE idstatointervento='.prepare($id_record).' AND `can_delete`=1';
        }

        $dbo->query($query);

        flash()->info(tr('Stato attività eliminato.'));

        break;
}
