<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            if ($dbo->fetchNum('SELECT * FROM `dt_causalet` WHERE `descrizione`='.prepare($descrizione).' AND `id`!='.prepare($id_record)) == 0) {
                $predefined = post('predefined');
                if (!empty($predefined)) {
                    $dbo->query('UPDATE dt_causalet SET predefined = 0');
                }

                $dbo->update('dt_causalet', [
                    'descrizione' => $descrizione,
                    'predefined' => $predefined,
                ], ['id' => $id_record]);

                flash()->info(tr('Salvataggio completato!'));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione", [
                    '_TYPE_' => 'causale',
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            if ($dbo->fetchNum('SELECT * FROM `dt_causalet` WHERE `descrizione`='.prepare($descrizione)) == 0) {
                $dbo->insert('dt_causalet', [
                    'descrizione' => $descrizione,
                ]);
                $id_record = $dbo->lastInsertedID();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'causale',
                ]));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione", [
                    '_TYPE_' => 'causale',
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':

        $documenti = $dbo->fetchNum('SELECT id FROM dt_ddt WHERE idcausalet='.prepare($id_record).'
                     UNION SELECT id FROM co_documenti WHERE idcausalet='.prepare($id_record));

        if (isset($id_record) && empty($documenti)) {
            $dbo->query('DELETE FROM `dt_causalet` WHERE `id`='.prepare($id_record));
            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo.', [
                '_TYPE_' => 'causale',
            ]));
        } else {
            flash()->error(tr('Sono presenti dei documenti collegati a questa causale'));
        }

        break;
}
