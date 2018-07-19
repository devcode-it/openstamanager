<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':

        $nome = filter('nome');

        if (isset($nome)) {
            $array = [
                'nome' => $nome,
                'filiale' => post('filiale'),
                'iban' => post('iban'),
                'bic' => post('bic'),
                'id_pianodeiconti3' => post('id_pianodeiconti3'),
                'note' => post('note'),
            ];

            if (!empty($id_record)) {
                $dbo->update('co_banche', $array, ['id' => $id_record]);
            }

            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'add':
        $nome = filter('nome');

        if (isset($nome)) {
            $dbo->query('INSERT INTO `co_banche` (`nome`) VALUES ('.prepare($nome).')');
            $id_record = $dbo->lastInsertedID();

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $nome]);
            }

            flash()->info(tr('Aggiunta nuova  _TYPE_', [
                '_TYPE_' => 'banca',
            ]));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'delete':

       $documenti = $dbo->fetchNum('SELECT idanagrafica FROM an_anagrafiche WHERE idbanca_vendite='.prepare($id_record).'
									UNION SELECT idanagrafica FROM an_anagrafiche WHERE idbanca_acquisti='.prepare($id_record));

        if (isset($id_record) && empty($documenti)) {
            $dbo->query('DELETE FROM `co_banche` WHERE `id`='.prepare($id_record));
            flash()->info(tr('_TYPE_ eliminata con successo!', [
                '_TYPE_' => 'Banca',
            ]));
        } else {
            $array = [
                'deleted' => 1,
            ];

            $dbo->update('co_banche', $array, ['id' => $id_record]);

            flash()->info(tr('_TYPE_ eliminata con successo!', [
                '_TYPE_' => 'Banca',
            ]));
        }

        break;
}
