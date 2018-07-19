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
                flash()->info(tr('Salvataggio completato!'));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione!", [
                    '_TYPE_' => 'IVA',
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
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

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'IVA',
                ]));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione!", [
                    '_TYPE_' => 'IVA',
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'delete':
        if (isset($id_record)) {
            $dbo->query('DELETE FROM `co_iva` WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'IVA',
            ]));
        }

        break;
}
