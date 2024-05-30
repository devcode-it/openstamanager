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

use Modules\Anagrafiche\Sede;

$operazione = filter('op');

switch ($operazione) {
    case 'addsede':
        if (!empty(post('nomesede'))) {
            $opt_out_newsletter = post('disable_newsletter_add');
            $dbo->insert('an_sedi', [
                'idanagrafica' => $id_parent,
                'nomesede' => post('nomesede'),
                'indirizzo' => post('indirizzo'),
                'citta' => post('citta'),
                'cap' => post('cap'),
                'provincia' => strtoupper(post('provincia')),
                'km' => post('km'),
                'id_nazione' => !empty(post('id_nazione')) ? post('id_nazione') : null,
                'idzona' => !empty(post('idzona')) ? post('idzona') : 0,
                'cellulare' => post('cellulare'),
                'telefono' => post('telefono'),
                'email' => post('email'),
                'enable_newsletter' => empty($opt_out_newsletter),
                'codice_destinatario' => post('codice_destinatario'),
                'is_automezzo' => post('is_automezzo_add'),
                'is_rappresentante_fiscale' => post('is_rappresentante_fiscale_add'),
                'targa' => post('targa'),
                'nome' => post('nome'),
                'descrizione' => post('descrizione'),
            ]);

            $id_record = $dbo->lastInsertedID();

            $id_referenti = (array) post('id_referenti');
            foreach ($id_referenti as $id_referente) {
                $dbo->update('an_referenti', [
                    'idsede' => $id_record,
                ], [
                    'id' => $id_referente,
                ]);
            }

            if (isAjaxRequest() && !empty($id_record)) {
                echo json_encode(['id' => $id_record, 'text' => post('nomesede').' - '.post('citta')]);
            }

            flash()->info(tr('Aggiunta una nuova sede!'));
        } else {
            flash()->warning(tr('Errore durante aggiunta della sede'));
        }

        $sede = Sede::find($id_record);
        $sede->save();

        break;

    case 'updatesede':
        $opt_out_newsletter = post('disable_newsletter');
        $sede = Sede::find($id_record);

        $dbo->update('an_sedi', [
            'nomesede' => post('nomesede'),
            'indirizzo' => post('indirizzo'),
            'citta' => post('citta'),
            'cap' => post('cap'),
            'provincia' => strtoupper(post('provincia')),
            'id_nazione' => !empty(post('id_nazione')) ? post('id_nazione') : null,
            'telefono' => post('telefono'),
            'cellulare' => post('cellulare'),
            'email' => post('email'),
            'enable_newsletter' => empty($opt_out_newsletter),
            'codice_destinatario' => post('codice_destinatario'),
            'is_rappresentante_fiscale' => post('is_rappresentante_fiscale'),
            'piva' => post('piva'),
            'codice_fiscale' => post('codice_fiscale'),
            'is_automezzo' => post('is_automezzo'),
            'idzona' => post('idzona'),
            'km' => post('km'),
            'note' => post('note'),
            'gaddress' => post('gaddress'),
            'lat' => post('lat'),
            'lng' => post('lng'),
            'targa' => post('targa'),
            'nome' => post('nome'),
            'descrizione' => post('descrizione'),
        ], ['id' => $id_record]);

        $referenti = $dbo->fetchArray('SELECT id FROM an_referenti WHERE idsede = '.$id_record);
        $id_referenti = (array) post('id_referenti');
        $refs = array_diff($referenti, $id_referenti);

        foreach ($id_referenti as $id_referente) {
            $dbo->update('an_referenti', [
                'idsede' => $id_record,
            ], [
                'id' => $id_referente,
            ]);
        }

        foreach ($refs as $ref) {
            $dbo->update('an_referenti', [
                'idsede' => 0,
            ], [
                'id' => $ref,
            ]);
        }

        $sede->save();

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'deletesede':
        $id = filter('id');
        $dbo->query('DELETE FROM `an_sedi` WHERE `id` = '.prepare($id).'');

        flash()->info(tr('Sede eliminata!'));

        break;
}
