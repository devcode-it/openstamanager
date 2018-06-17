<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $descrizione = post('descrizione');
        $colore = post('colore');
		$completato = post('completato');

        // Aggiorna
        $query = 'UPDATE in_statiintervento SET descrizione='.prepare($descrizione).',  colore='.prepare($colore).', completato='.prepare($completato).' WHERE idstatointervento='.prepare($id_record);
        $dbo->query($query);

        $_SESSION['infos'][] = tr('Informazioni salvate correttamente.');

        break;

    case 'add':
        $idstatointervento = post('idstatointervento');
        $descrizione = post('descrizione');
        $colore = post('colore');

        //controllo idstatointervento che non sia duplicato
        if (count($dbo->fetchArray('SELECT idstatointervento FROM in_statiintervento WHERE idstatointervento='.prepare($idstatointervento))) > 0) {
            $_SESSION['errors'][] = tr('Stato di intervento già esistente.');
        } else {
            $query = 'INSERT INTO in_statiintervento(idstatointervento, descrizione, colore) VALUES ('.prepare($idstatointervento).', '.prepare($descrizione).', '.prepare($colore).')';
            $dbo->query($query);
            $id_record = $idstatointervento;
            $_SESSION['infos'][] = tr('Nuovo stato di intervento aggiunto.');
        }

        break;

    case 'delete':

        //scelgo se settare come eliminato o cancellare direttamente la riga se non è stato utilizzato negli interventi
        if (count($dbo->fetchArray('SELECT id FROM in_interventi WHERE idstatointervento='.prepare($id_record))) > 0) {
            $query = 'UPDATE in_statiintervento SET deleted = 1 WHERE idstatointervento='.prepare($id_record).' AND `can_delete`=1';
        } else {
            $query = 'DELETE FROM in_statiintervento  WHERE idstatointervento='.prepare($id_record).' AND `can_delete`=1';
        }

        $dbo->query($query);

        $_SESSION['infos'][] = tr('Stato di intervento eliminato.');

        break;
}
