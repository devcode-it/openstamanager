<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'delete-bulk':

       
		$idtipoanagrafica_azienda = $dbo->fetchArray("SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Azienda'")[0]['idtipoanagrafica'];

		foreach ($id_records as $id) {
			$anagrafica = $dbo->fetchArray('SELECT an_tipianagrafiche.idtipoanagrafica FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica=an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE idanagrafica='.prepare($id));
			$tipi = array_column($anagrafica, 'idtipoanagrafica');

			// Se l'anagrafica non è di tipo Azienda
			if (!in_array($idtipoanagrafica_azienda, $tipi)) {
				$dbo->query('UPDATE an_anagrafiche SET deleted_at = NOW() WHERE idanagrafica = '.prepare($id).Modules::getAdditionalsQuery($id_module));
			}
		}

		flash()->info(tr('Anagrafiche eliminate!'));
        

        break;
}


if (App::debug()) {
	$operations = [
		'delete-bulk' => tr('Elimina selezionati'),
	];
}


return $operations;
