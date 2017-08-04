<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $descrizione = post('descrizione');
        $colore = post('colore');

        $query = 'UPDATE in_statiintervento SET colore='.prepare($colore).' WHERE idstatointervento='.prepare($id_record);
        $dbo->query($query);

        // Aggiorna descrizione solo se non è uno stato di default
        $query = 'UPDATE in_statiintervento SET descrizione='.prepare($descrizione).' WHERE idstatointervento='.prepare($id_record).' AND `default`=0';
        $dbo->query($query);

        $_SESSION['infos'][] = _('Informazioni salvate correttamente!');

        break;

    case 'add':
        $idstatointervento = post('idstatointervento');
        $descrizione = post('descrizione');
        $colore = post('colore');

        $query = 'INSERT INTO in_statiintervento(idstatointervento, descrizione, colore) VALUES ('.prepare($idstatointervento).', '.prepare($descrizione).', '.prepare($colore).')';
        $dbo->query($query);
        $id_record = $idstatointervento;

        $_SESSION['infos'][] = _('Nuovo stato di intervento aggiunto!');

        break;

    case 'delete':
        $query = 'DELETE FROM in_statiintervento WHERE idstatointervento='.prepare($id_record).' AND `default`=0';
        $dbo->query($query);

        $_SESSION['infos'][] = _('Stato di intervento eliminato!');

        break;
}
