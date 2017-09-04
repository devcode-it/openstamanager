<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');
        $percentuale = filter('percentuale');
        $indetraibile = filter('indetraibile');
        $dicitura = filter('dicitura');

        if (isset($descrizione) && isset($percentuale) && isset($indetraibile)) {
            if ($dbo->fetchNum('SELECT * FROM `co_iva` WHERE `descrizione`='.prepare($descrizione).' AND `id`!='.prepare($id_record)) == 0) {
                $dbo->query('UPDATE `co_iva` SET `descrizione`='.prepare($descrizione).', `percentuale`='.prepare($percentuale).', `indetraibile`='.prepare($indetraibile).', `dicitura`='.prepare($dicitura).' WHERE `id`='.prepare($id_record));
                $_SESSION['infos'][] = tr('Salvataggio completato!');
            } else {
                $_SESSION['errors'][] = str_replace('_TYPE_', 'IVA', tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione!"));
            }
        } else {
            $_SESSION['errors'][] = tr('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');
        $percentuale = filter('percentuale');
        $indetraibile = filter('indetraibile');

        if (isset($descrizione) && isset($percentuale) && isset($indetraibile)) {
            if ($dbo->fetchNum('SELECT * FROM `co_iva` WHERE `descrizione`='.prepare($descrizione)) == 0) {
                $dbo->query('INSERT INTO `co_iva` (`descrizione`, `percentuale`, `indetraibile`) VALUES ('.prepare($descrizione).', '.prepare($percentuale).', '.prepare($indetraibile).')');
                $id_record = $dbo->lastInsertedID();

                $_SESSION['infos'][] = str_replace('_TYPE_', 'IVA', tr('Aggiunta nuova tipologia di _TYPE_'));
            } else {
                $_SESSION['errors'][] = str_replace('_TYPE_', 'IVA', tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione!"));
            }
        } else {
            $_SESSION['errors'][] = tr('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'delete':
        if (isset($id_record)) {
            $dbo->query('DELETE FROM `co_iva` WHERE `id`='.prepare($id_record));

            $_SESSION['infos'][] = str_replace('_TYPE_', 'IVA', tr('Tipologia di _TYPE_ eliminata con successo!'));
        }

        break;
}
