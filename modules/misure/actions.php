<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $valore = filter('valore');

        if (isset($valore)) {
            if ($dbo->fetchNum('SELECT * FROM `mg_unitamisura` WHERE `valore`='.prepare($valore).' AND `id`!='.prepare($id_record)) == 0) {
                $dbo->query('UPDATE `mg_unitamisura` SET `valore`='.prepare($valore).' WHERE `id`='.prepare($id_record));
                $_SESSION['infos'][] = tr('Salvataggio completato.');
            } else {
                $_SESSION['errors'][] = tr("E' già presente una tipologia di _TYPE_ con lo stesso valore.", [
    '_TYPE_' => 'unità di misura',
]);
            }
        } else {
            $_SESSION['errors'][] = tr('Ci sono stati alcuni errori durante il salvataggio.');
        }

        break;

    case 'add':
        $valore = filter('valore');

        if (isset($valore)) {
            if ($dbo->fetchNum('SELECT * FROM `mg_unitamisura` WHERE `valore`='.prepare($valore)) == 0) {
                $dbo->query('INSERT INTO `mg_unitamisura` (`valore`) VALUES ('.prepare($valore).')');

                $id_record = $dbo->lastInsertedID();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $valore, 'text' => $valore]);
                }

                $_SESSION['infos'][] = tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'unità di misura',
                ]);
            } else {
                $_SESSION['errors'][] = tr("E' già presente una tipologia di _TYPE_ con lo stesso valore.", [
                    '_TYPE_' => 'unità di misura',
                ]);
            }
        } else {
            $_SESSION['errors'][] = tr('Ci sono stati alcuni errori durante il salvataggio.');
        }

        break;

    case 'delete':

        $righe = $dbo->fetchNum('SELECT id FROM co_righe_documenti WHERE um='.prepare($records[0]['valore']).'
             UNION SELECT id FROM dt_righe_ddt WHERE um='.prepare($records[0]['valore']).'
             UNION SELECT id FROM or_righe_ordini WHERE um='.prepare($records[0]['valore']).'
             UNION SELECT id FROM co_righe2_contratti WHERE um='.prepare($records[0]['valore']).'
             UNION SELECT id FROM mg_articoli WHERE um='.prepare($records[0]['valore']).'
             UNION SELECT id FROM co_righe_preventivi WHERE um='.prepare($records[0]['valore']));

        if (isset($id_record) && empty($righe)) {
            $dbo->query('DELETE FROM `mg_unitamisura` WHERE `id`='.prepare($id_record));
            $_SESSION['infos'][] = tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'unità di misura',
            ]);
        } else {
            $_SESSION['errors'][] = tr('Sono presenti righe collegate a questa unità di misura.');
        }

        break;
}
