<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');
        $percentuale = filter('percentuale');
        $indetraibile = filter('indetraibile');

        if (isset($descrizione) && isset($percentuale) && isset($indetraibile)) {
            if ($dbo->fetchNum('SELECT * FROM `co_ritenutaacconto` WHERE `descrizione`='.prepare($descrizione).' AND `id`!='.prepare($id_record)) == 0) {
                $dbo->query('UPDATE `co_ritenutaacconto` SET `descrizione`='.prepare($descrizione).', `percentuale`='.prepare($percentuale).', `indetraibile`='.prepare($indetraibile).' WHERE `id`='.prepare($id_record));
                $_SESSION['infos'][] = _('Salvataggio completato!');
            } else {
                $_SESSION['errors'][] = str_replace('_TYPE_', "ritenuta d'acconto", _("E' già presente una tipologia di _TYPE_ con la stessa descrizione!"));
            }
        } else {
            $_SESSION['errors'][] = _('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');
        $percentuale = filter('percentuale');
        $indetraibile = filter('indetraibile');

        if (isset($descrizione) && isset($percentuale) && isset($indetraibile)) {
            if ($dbo->fetchNum('SELECT * FROM `co_ritenutaacconto` WHERE `descrizione`='.prepare($descrizione)) == 0) {
                $dbo->query('INSERT INTO `co_ritenutaacconto` (`descrizione`, `percentuale`, `indetraibile`) VALUES ('.prepare($descrizione).', '.prepare($percentuale).', '.prepare($indetraibile).')');
                $id_record = $dbo->lastInsertedID();

                $_SESSION['infos'][] = str_replace('_TYPE_', "ritenuta d'acconto", _('Aggiunta nuova tipologia di _TYPE_'));
            } else {
                $_SESSION['errors'][] = str_replace('_TYPE_', "ritenuta d'acconto", _("E' già presente una tipologia di _TYPE_ con la stessa descrizione!"));
            }
        } else {
            $_SESSION['errors'][] = _('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'delete':
        if (isset($id_record)) {
            $dbo->query('DELETE FROM `co_ritenutaacconto` WHERE `id`='.prepare($id_record));
            $_SESSION['infos'][] = str_replace('_TYPE_', "ritenuta d'acconto", _('Tipologia di _TYPE_ eliminata con successo!'));
        }

        break;
}
