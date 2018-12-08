<?php

include_once __DIR__.'/../../core.php';

$operazione = filter('op');

switch ($operazione) {
    case 'addsede':
        $dbo->insert('an_sedi', [
            'idanagrafica' => $id_parent,
            'nomesede' => post('nomesede'),
            'indirizzo' => post('indirizzo'),
            'citta' => post('citta'),
            'cap' => post('cap'),
            'provincia' => post('provincia'),
            'km' => post('km'),
            'cellulare' => post('cellulare'),
            'telefono' => post('telefono'),
            'email' => post('email'),
            'idzona' => post('idzona'),
        ]);
        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Aggiunta una nuova sede!'));

        break;

    case 'updatesede':
        $array = [
            'nomesede' => post('nomesede'),
            'indirizzo' => post('indirizzo'),
            'codice_destinatario' => post('codice_destinatario'),
            'piva' => post('piva'),
            'codice_fiscale' => post('codice_fiscale'),
            'citta' => post('citta'),
            'cap' => post('cap'),
            'provincia' => post('provincia'),
            'km' => post('km'),
            'cellulare' => post('cellulare'),
            'telefono' => post('telefono'),
            'email' => post('email'),
            'fax' => post('fax'),
            'id_nazione' => !empty(post('id_nazione')) ? post('id_nazione') : null,
            'idzona' => post('idzona'),
            'gaddress' => post('gaddress'),
            'lat' => post('lat'),
            'lng' => post('lng'),
        ];

        $dbo->update('an_sedi', $array, ['id' => $id_record]);

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'deletesede':
        $dbo->query('DELETE FROM `an_sedi` WHERE `id`='.prepare($id_record));

        flash()->info(tr('Sede eliminata!'));

        break;
}
