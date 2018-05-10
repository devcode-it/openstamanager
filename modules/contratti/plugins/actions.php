<?php
include_once __DIR__.'/../../../core.php';

include_once $docroot.'/modules/fatture/modutil.php';

switch (post('op')) {
	
	
	
	 /*
        Gestione righe generiche
    */
    case 'addriga':
		$descrizione = post('descrizione');
		$qta = post('qta');
		$um = post('um');
		$idiva = post('idiva');
		$prezzo_vendita = post('prezzo_vendita');
		$prezzo_acquisto = post('prezzo_acquisto');

		$sconto_unitario = $post['sconto'];
		$tipo_sconto = $post['tipo_sconto'];
		$sconto = ($tipo_sconto == 'PRC') ? ($prezzo_vendita * $sconto_unitario) / 100 : $sconto_unitario;
		$sconto = $sconto * $qta;

		//Calcolo iva
		$rs_iva = $dbo->fetchArray('SELECT * FROM co_iva WHERE id='.prepare($idiva));
		$desc_iva = $rs_iva[0]['descrizione'];

		$iva = (($prezzo_vendita * $qta) - $sconto) * $rs_iva[0]['percentuale'] / 100;
		
		$idcontratto_riga = $post['idcontratto_riga'];
		
		
		$dbo->query('INSERT INTO co_righe_contratti_materiali(descrizione, qta, um, prezzo_vendita, prezzo_acquisto, idiva, desc_iva, iva, sconto, sconto_unitario, tipo_sconto, id_riga_contratto) VALUES ('.prepare($descrizione).', '.prepare($qta).', '.prepare($um).', '.prepare($prezzo_vendita).', '.prepare($prezzo_acquisto).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($idcontratto_riga).')');

	break;
		
		
    case 'editriga':
	
	
	$idriga = post('idriga');
	$descrizione = post('descrizione');
	$qta = post('qta');
	$um = post('um');
	$idiva = post('idiva');
	$prezzo_vendita = post('prezzo_vendita');
	$prezzo_acquisto = post('prezzo_acquisto');

	$sconto_unitario = $post['sconto'];
	$tipo_sconto = $post['tipo_sconto'];
	$sconto = ($tipo_sconto == 'PRC') ? ($prezzo_vendita * $sconto_unitario) / 100 : $sconto_unitario;
	$sconto = $sconto * $qta;

	//Calcolo iva
	$rs_iva = $dbo->fetchArray('SELECT * FROM co_iva WHERE id='.prepare($idiva));
	$desc_iva = $rs_iva[0]['descrizione'];

	$iva = (($prezzo_vendita * $qta) - $sconto) * $rs_iva[0]['percentuale'] / 100;

	$dbo->query('UPDATE  co_righe_contratti_materiali SET '.
		' descrizione='.prepare($descrizione).','.
		' qta='.prepare($qta).','.
		' um='.prepare($um).','.
		' prezzo_vendita='.prepare($prezzo_vendita).','.
		' prezzo_acquisto='.prepare($prezzo_acquisto).','.
		' idiva='.prepare($idiva).','.
		' desc_iva='.prepare($desc_iva).','.
		' iva='.prepare($iva).','.
		' sconto='.prepare($sconto).','.
		' sconto_unitario='.prepare($sconto_unitario).','.
		' tipo_sconto='.prepare($tipo_sconto).
		' WHERE id='.prepare($idriga));
	
	
	break;
	
	
	case 'delriga':
	
        $idriga = post('idriga');
        $dbo->query('DELETE FROM co_righe_contratti_materiali WHERE id='.prepare($idriga).' '.Modules::getAdditionalsQuery($id_module));

	break;
}
	
	
	
?>