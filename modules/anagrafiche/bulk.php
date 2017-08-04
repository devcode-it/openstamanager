<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'delete-bulk':
        $id_azienda = $dbo->fetchArray("SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Azienda'")[0]['idtipoanagrafica'];

        foreach ($id_records as $id) {
            // Disattivo l'anagrafica, solo se questa non è l'azienda principale
            if (strpos($records[0]['idtipianagrafica'], $id_azienda) === false) {
                $dbo->query('UPDATE an_anagrafiche SET deleted = 1 WHERE idanagrafica = '.prepare($id).Modules::getAdditionalsQuery($id_module));
            }
        }

        $_SESSION['infos'][] = _('Anagrafica eliminata!');

        break;
}

return [
    'delete' => 'delete-bulk',
];
