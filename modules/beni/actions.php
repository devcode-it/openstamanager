<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $descrizione = post('descrizione');

        if ($dbo->fetchNum('SELECT * FROM `dt_aspettobeni` WHERE `descrizione`='.prepare($descrizione).' AND `id`!='.prepare($id_record)) == 0) {
            $dbo->query('UPDATE `dt_aspettobeni` SET `descrizione`='.prepare($descrizione).' WHERE `id`='.prepare($id_record));
            $_SESSION['infos'][] = tr('Salvataggio completato!');
        } else {
            $_SESSION['errors'][] = str_replace('_TYPE_', 'bene', tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione!"));
        }

        break;

    case 'add':
        $descrizione = post('descrizione');

        if ($dbo->fetchNum('SELECT * FROM `dt_aspettobeni` WHERE `descrizione`='.prepare($descrizione)) == 0) {
            $dbo->query('INSERT INTO `dt_aspettobeni` (`descrizione`) VALUES ('.prepare($descrizione).')');

            $id_record = $dbo->lastInsertedID();

            $_SESSION['infos'][] = str_replace('_TYPE_', 'bene', tr('Aggiunta nuova tipologia di _TYPE_'));
        } else {
            $_SESSION['errors'][] = str_replace('_TYPE_', 'bene', tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione!"));
        }

        break;

    case 'delete':
        if (isset($id_record)) {
            $dbo->query('DELETE FROM `dt_aspettobeni` WHERE `id`='.prepare($id_record));
            $_SESSION['infos'][] = str_replace('_TYPE_', 'bene', tr('Tipologia di _TYPE_ eliminata con successo!'));
        }

        break;
}
