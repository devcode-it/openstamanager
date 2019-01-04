<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');

        if ($dbo->fetchNum('SELECT * FROM `dt_porto` WHERE `descrizione`='.prepare($descrizione).' AND `id`!='.prepare($id_record)) == 0) {
            $predefined = post('predefined');
            if (!empty($predefined)) {
                $dbo->query('UPDATE dt_porto SET predefined = 0');
            }

            $dbo->update('dt_porto', [
                'descrizione' => $descrizione,
                'predefined' => $predefined,
            ], ['id' => $id_record]);

            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione", [
                '_TYPE_' => 'porto',
            ]));
        }
        break;

    case 'add':
        $descrizione = filter('descrizione');

        if ($dbo->fetchNum('SELECT * FROM `dt_porto` WHERE `descrizione`='.prepare($descrizione)) == 0) {
            $dbo->insert('dt_porto', [
                'descrizione' => $descrizione,
            ]);
            $id_record = $dbo->lastInsertedID();

            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'porto',
            ]));
        } else {
            flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione", [
                '_TYPE_' => 'porto',
            ]));
        }

        break;

    case 'delete':
        $documenti = $dbo->fetchNum('SELECT id FROM dt_ddt WHERE idporto='.prepare($id_record).'
            UNION SELECT id FROM co_documenti WHERE idporto='.prepare($id_record).'
            UNION SELECT id FROM co_preventivi WHERE idporto='.prepare($id_record));

        if (isset($id_record) && empty($documenti)) {
            $dbo->query('DELETE FROM `dt_porto` WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'porto',
            ]));
        } else {
            flash()->error(tr('Sono presenti dei documenti collegati a questo porto.'));
        }

        break;
}
