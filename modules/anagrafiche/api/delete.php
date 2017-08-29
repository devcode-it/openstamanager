<?php

switch ($resource) {
    case 'delete_anagrafica':
        $id_azienda = $dbo->fetchArray("SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Azienda'")[0]['idtipoanagrafica'];

        $records = $dbo->fetchArray('SELECT an_tipianagrafiche.idtipoanagrafica FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica=an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE idanagrafica='.prepare($request['id']));
        $tipi = array_column($records, 'idtipoanagrafica');

        // Se l'anagrafica non è l'azienda principale, la disattivo
        if (!in_array($id_azienda, $tipi)) {
            $dbo->query('UPDATE an_anagrafiche SET deleted = 1 WHERE idanagrafica = '.prepare($request['id']));
        }

        break;
}

return [
    'delete_anagrafica',
];
