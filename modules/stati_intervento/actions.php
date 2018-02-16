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

        $_SESSION['infos'][] = tr('Informazioni salvate correttamente.');

        break;

    case 'add':
        $idstatointervento = post('idstatointervento');
        $descrizione = post('descrizione');
        $colore = post('colore');
		
		
	
		if (count($dbo->fetchArray('SELECT idstatointervento FROM in_statiintervento WHERE idstatointervento='.prepare($idstatointervento).''))>0){
			
		   $_SESSION['errors'][] = tr('Stato di intervento già esistente.');
			   
		}else{
							
							
			$query = 'INSERT INTO in_statiintervento(idstatointervento, descrizione, colore) VALUES ('.prepare($idstatointervento).', '.prepare($descrizione).', '.prepare($colore).')';
			$dbo->query($query);
			$id_record = $idstatointervento;

			$_SESSION['infos'][] = tr('Nuovo stato di intervento aggiunto.');
		
		}
		
        break;

    case 'delete':
        $query = 'UPDATE in_statiintervento SET deleted = 1 WHERE idstatointervento='.prepare($id_record).' AND `default`=0';
        
		$dbo->query($query);

        $_SESSION['infos'][] = tr('Stato di intervento eliminato.');

        break;
}
