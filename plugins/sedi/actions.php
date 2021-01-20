<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';

$operazione = filter('op');

switch ($operazione) {
    case 'addsede':
         if (!empty(post('nomesede'))) {
             $dbo->insert('an_sedi', [
                'idanagrafica' => $id_parent,
                'nomesede' => post('nomesede'),
                'indirizzo' => post('indirizzo'),
                'codice_destinatario' => post('codice_destinatario'),
                'citta' => post('citta'),
                'cap' => post('cap'),
                'provincia' => strtoupper(post('provincia')),
                'km' => post('km'),
                'cellulare' => post('cellulare'),
                'telefono' => post('telefono'),
                'email' => post('email'),
                'id_nazione' => !empty(post('id_nazione')) ? post('id_nazione') : null,
                'idzona' => !empty(post('id_zona')) ? post('id_zona') : 0,
            ]);
             $id_record = $dbo->lastInsertedID();

             if (isAjaxRequest() && !empty($id_record)) {
                 echo json_encode(['id' => $id_record, 'text' => post('nomesede').' - '.post('citta')]);
             }

             flash()->info(tr('Aggiunta una nuova sede!'));
         } else {
             flash()->warning(tr('Errore durante aggiunta della sede'));
         }

        break;

    case 'updatesede':
        $dbo->update('an_sedi', [
            'nomesede' => post('nomesede'),
            'indirizzo' => post('indirizzo'),
            'codice_destinatario' => post('codice_destinatario'),
            'piva' => post('piva'),
            'codice_fiscale' => post('codice_fiscale'),
            'citta' => post('citta'),
            'cap' => post('cap'),
            'provincia' => strtoupper(post('provincia')),
            'km' => post('km'),
            'cellulare' => post('cellulare'),
            'telefono' => post('telefono'),
            'email' => post('email'),
            'fax' => post('fax'),
            'id_nazione' => !empty(post('id_nazione')) ? post('id_nazione') : null,
            'idzona' => post('idzona'),
            'note' => post('note'),
            'gaddress' => post('gaddress'),
            'lat' => post('lat'),
            'lng' => post('lng'),
        ], ['id' => $id_record]);

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'deletesede':
        $id = filter('id');
        $dbo->query('DELETE FROM `an_sedi` WHERE `id` = '.prepare($id).'');

        flash()->info(tr('Sede eliminata!'));

        break;
}
