<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $valore = filter('valore');

        if (isset($valore)) {
            if ($dbo->fetchNum('SELECT * FROM `mg_unitamisura` WHERE `valore`='.prepare($valore).' AND `id`!='.prepare($id_record)) == 0) {
                $dbo->query('UPDATE `mg_unitamisura` SET `valore`='.prepare($valore).' WHERE `id`='.prepare($id_record));
                $_SESSION['infos'][] = tr('Salvataggio completato!');
            } else {
                $_SESSION['errors'][] = str_replace('_TYPE_', 'unità di misura', tr("E' già presente una tipologia di _TYPE_ con lo stesso valore!"));
            }
        } else {
            $_SESSION['errors'][] = tr('Ci sono stati alcuni errori durante il salvataggio!');
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

                $_SESSION['infos'][] = str_replace('_TYPE_', 'unità di misura', tr('Aggiunta nuova tipologia di _TYPE_'));
            } else {
                $_SESSION['errors'][] = str_replace('_TYPE_', 'unità di misura', tr("E' già presente una tipologia di _TYPE_ con lo stesso valore!"));
            }
        } else {
            $_SESSION['errors'][] = tr('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'delete':
        if (isset($id_record)) {
            $dbo->query('DELETE FROM `mg_unitamisura` WHERE `id`='.prepare($id_record));
            $_SESSION['infos'][] = str_replace('_TYPE_', 'unità di misura', tr('Tipologia di _TYPE_ eliminata con successo!'));
        }

        break;
}
