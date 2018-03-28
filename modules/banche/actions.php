<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
	
        $nome = filter('nome');

        if (isset($nome)) {
         
			$array = [
				'nome' => $nome,
				'filiale' => $post['filiale'],
				'IBAN' => $post['IBAN'],
				'BIC' => $post['BIC'],
				'idconto_vendite' => $post['idconto_vendite'],
				'idconto_acquisti' => $post['idconto_acquisti'],
				'note' => $post['note'],
			];

			if (!empty($id_record)) {
				$dbo->update('co_banche', $array, ['id_record' => $id_record]);
			} 
        
            $_SESSION['infos'][] = tr('Salvataggio completato!');
        } else {
            $_SESSION['errors'][] = tr('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'add':
        $nome = filter('nome');

        if (isset($nome)) {
            $dbo->query('INSERT INTO `co_banche` (`nome`) VALUES ('.prepare($nome).')');
            $id_record = $dbo->lastInsertedID();

            $_SESSION['infos'][] = tr('Aggiunta nuova  _TYPE_', [
                '_TYPE_' => 'banca',
            ]);
        } else {
            $_SESSION['errors'][] = tr('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'delete':
        if (!empty($id_record)) {
            $dbo->query('DELETE FROM `co_banche` WHERE `id`='.prepare($id_record));
            $_SESSION['infos'][] = tr('_TYPE_ eliminata con successo!', [
                '_TYPE_' => 'Banca',
            ]);
        }

        break;
}
