<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        if (isset($id_record)) {
            $database->update('mg_causali_movimenti', [
                'nome' => post('nome'),
                'movimento_carico' => post('movimento_carico'),
                'descrizione' => post('descrizione'),
            ], [
                'id' => $id_record,
            ]);
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'add':
        $database->insert('mg_causali_movimenti', [
            'nome' => post('nome'),
            'movimento_carico' => post('movimento_carico'),
            'descrizione' => post('descrizione'),
        ]);
        $id_record = $database->lastInsertedID();

        break;

    case 'delete':
        if (isset($id_record)) {
            $dbo->query('DELETE FROM `mg_causali_movimenti` WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'movimento predefinito',
            ]));
        }

        break;
}
