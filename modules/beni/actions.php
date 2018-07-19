<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $descrizione = post('descrizione');

        if ($dbo->fetchNum('SELECT * FROM `dt_aspettobeni` WHERE `descrizione`='.prepare($descrizione).' AND `id`!='.prepare($id_record)) == 0) {
            $dbo->query('UPDATE `dt_aspettobeni` SET `descrizione`='.prepare($descrizione).' WHERE `id`='.prepare($id_record));
            flash()->info(tr('Salvataggio completato.'));
        } else {
            flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione.", [
                '_TYPE_' => 'bene',
            ]));
        }

        break;

    case 'add':
        $descrizione = post('descrizione');

        if ($dbo->fetchNum('SELECT * FROM `dt_aspettobeni` WHERE `descrizione`='.prepare($descrizione)) == 0) {
            $dbo->query('INSERT INTO `dt_aspettobeni` (`descrizione`) VALUES ('.prepare($descrizione).')');

            $id_record = $dbo->lastInsertedID();

            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'bene',
            ]));
        } else {
            flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione.", [
                '_TYPE_' => 'bene',
            ]));
        }

        break;

    case 'delete':

        $documenti = $dbo->fetchNum('SELECT id FROM dt_ddt WHERE idaspettobeni='.prepare($id_record).'
                     UNION SELECT id FROM co_documenti WHERE idaspettobeni='.prepare($id_record));

        if (isset($id_record) && empty($documenti)) {
            $dbo->query('DELETE FROM `dt_aspettobeni` WHERE `id`='.prepare($id_record));
            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo.', [
                '_TYPE_' => 'bene',
            ]));
        } else {
            flash()->error(tr('Sono presenti dei documenti collegati a questo aspetto beni.'));
        }

        break;
}
