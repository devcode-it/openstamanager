<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'delete-bulk':

        if (App::debug()) {
            $id_azienda = $dbo->fetchOne("SELECT id FROM an_tipianagrafiche WHERE descrizione='Azienda'")['id'];

            foreach ($id_records as $id) {
                $anagrafica = $dbo->fetchArray('SELECT an_tipianagrafiche.id FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.id=an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE idanagrafica='.prepare($id));
                $tipi = array_column($anagrafica, 'id');

                // Se l'anagrafica non è l'azienda principale, la disattivo
                if (!in_array($id_azienda, $tipi)) {
                    $dbo->query('UPDATE an_anagrafiche SET deleted_at = NOW() WHERE idanagrafica = '.prepare($id).Modules::getAdditionalsQuery($id_module));
                }
            }

            flash()->info(tr('Anagrafiche eliminate!'));
        } else {
            flash()->warning(tr('Procedura in fase di sviluppo. Nessuna modifica apportata.'));
        }

        break;
}

return [
    'delete-bulk' => tr('Elimina selezione'),
];
