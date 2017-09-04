<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            if ($dbo->fetchNum('SELECT * FROM `dt_porto` WHERE `descrizione`='.prepare($descrizione).' AND `id`!='.prepare($id_record)) == 0) {
                $dbo->query('UPDATE `dt_porto` SET `descrizione`='.prepare($descrizione).' WHERE `id`='.prepare($id_record));
                $_SESSION['infos'][] = tr('Salvataggio completato!');
            } else {
                $_SESSION['errors'][] = str_replace('_TYPE_', 'porto', tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione!"));
            }
        } else {
            $_SESSION['errors'][] = tr('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            if ($dbo->fetchNum('SELECT * FROM `dt_porto` WHERE `descrizione`='.prepare($descrizione)) == 0) {
                $dbo->query('INSERT INTO `dt_porto` (`descrizione`) VALUES ('.prepare($descrizione).')');
                $id_record = $dbo->lastInsertedID();

                $_SESSION['infos'][] = str_replace('_TYPE_', 'porto', tr('Aggiunta nuova tipologia di _TYPE_'));
            } else {
                $_SESSION['errors'][] = str_replace('_TYPE_', 'porto', tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione!"));
            }
        } else {
            $_SESSION['errors'][] = tr('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'delete':
        $documenti = $dbo->fetchArray('SELECT id FROM dt_ddt WHERE idporto='.prepare($id_record).'
UNION SELECT id FROM co_documenti WHERE idporto='.prepare($id_record).'
UNION SELECT id FROM co_preventivi WHERE idporto='.prepare($id_record));

        if (isset($id_record) && !empty($documenti)) {
            $dbo->query('DELETE FROM `dt_porto` WHERE `id`='.prepare($id_record));
            $_SESSION['infos'][] = str_replace('_TYPE_', 'porto', tr('Tipologia di _TYPE_ eliminata con successo!'));
        }else{
            $_SESSION['errors'][] = tr('Sono presenti dei documenti collegati a questo porto!');
        }

        break;
}
