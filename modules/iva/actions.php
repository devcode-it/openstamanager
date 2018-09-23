<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $descrizione = post('descrizione');
        $codice = post('codice');

        $esente = post('esente');
        $percentuale = empty($esente) ? post('percentuale') : 0;

        if ($dbo->fetchNum('SELECT * FROM `co_iva` WHERE (`descrizione` = '.prepare($descrizione).' OR `codice` = '.prepare($codice).') AND `id` != '.prepare($id_record)) == 0) {
            $dbo->update('co_iva', [
                'descrizione' => $descrizione,
                'esente' => $esente,
                'percentuale' => $percentuale,
                'indetraibile' => post('indetraibile'),
                'dicitura' => post('dicitura'),
                'codice' => $codice,
                'codice_natura_fe' => post('codice_natura_fe'),
            ], ['id' => $id_record]);

            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stesse caratteristiche!", [
                '_TYPE_' => 'IVA',
            ]));
        }
        break;

    case 'add':
        $descrizione = post('descrizione');

        $esente = post('esente');
        $percentuale = empty($esente) ? post('percentuale') : 0;

        if ($dbo->fetchNum('SELECT * FROM `co_iva` WHERE `descrizione`='.prepare($descrizione)) == 0) {
            $dbo->insert('co_iva', [
                'descrizione' => $descrizione,
                'esente' => $esente,
                'percentuale' => $percentuale,
                'indetraibile' => post('indetraibile'),
            ]);
            $id_record = $dbo->lastInsertedID();

            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'IVA',
            ]));
        } else {
            flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione!", [
                '_TYPE_' => 'IVA',
            ]));
        }

        break;

    case 'delete':
        if (isset($id_record)) {
            $dbo->query('UPADTE `co_iva` SET deleted_at = NOW() WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'IVA',
            ]));
        }

        break;
}
