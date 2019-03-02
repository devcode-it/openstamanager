<?php

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');

        if ($dbo->fetchNum('SELECT * FROM `dt_spedizione` WHERE `descrizione`='.prepare($descrizione).' AND `id`!='.prepare($id_record)) == 0) {
            $predefined = post('predefined');
            if (!empty($predefined)) {
                $dbo->query('UPDATE dt_spedizione SET predefined = 0');
            }

            $dbo->update('dt_spedizione', [
                'descrizione' => $descrizione,
                'predefined' => $predefined,
            ], ['id' => $id_record]);

            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione", [
                '_TYPE_' => 'spedizione',
            ]));
        }
        break;

    case 'add':
        $descrizione = filter('descrizione');

        if ($dbo->fetchNum('SELECT * FROM `dt_spedizione` WHERE `descrizione`='.prepare($descrizione)) == 0) {
            $dbo->insert('dt_spedizione', [
                'descrizione' => $descrizione,
            ]);
            $id_record = $dbo->lastInsertedID();

            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'spedizione',
            ]));
        } else {
            flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione", [
                '_TYPE_' => 'spedizione',
            ]));
        }

        break;

    case 'delete':
        $documenti = $dbo->fetchNum('SELECT id FROM dt_ddt WHERE idspedizione='.prepare($id_record).'
            UNION SELECT id FROM co_documenti WHERE idspedizione='.prepare($id_record));

        if (isset($id_record) && empty($documenti)) {
            $dbo->query('DELETE FROM `dt_spedizione` WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'spedizione',
            ]));
        } else {
            flash()->error(tr('Sono presenti dei documenti collegati a questo porto.'));
        }

        break;
}
