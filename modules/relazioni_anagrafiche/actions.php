<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');
        $colore = filter('colore');

        if (isset($descrizione)) {
            if ($dbo->fetchNum('SELECT * FROM `an_relazioni` WHERE `descrizione`='.prepare($descrizione).' AND `id`!='.prepare($id_record)) == 0) {
                $dbo->query('UPDATE `an_relazioni` SET `descrizione`='.prepare($descrizione).', `colore`='.prepare($colore).' WHERE `id`='.prepare($id_record));
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente una relazione _NAME_.", [
                    '_TYPE_' =>  $descrizione,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio.'));
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');
        $colore = filter('colore');

        if (isset($descrizione)) {
            if ($dbo->fetchNum('SELECT * FROM `an_relazioni` WHERE `descrizione`='.prepare($descrizione)) == 0) {
                $dbo->query('INSERT INTO `an_relazioni` (`descrizione`, `colore` ) VALUES ('.prepare($descrizione).', '.prepare($colore).' )');

                $id_record = $dbo->lastInsertedID();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunta nuova relazione _NAME_', [
                    '_NAME_' => $descrizione,
                ]));
            } else {
                flash()->error(tr("E' già presente una relazione di _NAME_.", [
                    '_NAME_' => $descrizione,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio.'));
        }

        break;

    case 'delete':

        $righe = $dbo->fetchNum('SELECT idanagrafica FROM an_anagrafiche WHERE idrelazione='.prepare($id_record));

        if (isset($id_record) && empty($righe)) {
            $dbo->query('DELETE FROM `an_relazioni` WHERE `id`='.prepare($id_record));
            flash()->info(tr('Relazione _NAME_ eliminata con successo!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            flash()->error(tr('Sono presenti '.count($righe).' anagrafiche collegate a questa relazione.'));
        }

        break;
}
