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

use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Sede;

$operazione = filter('op');

switch ($operazione) {
    case 'addsede':
        if (!empty(post('nomesede'))) {
            $opt_out_newsletter = post('disable_newsletter_add');
            $sede = Sede::build(Anagrafica::find($id_parent));

            $sede->nomesede = post('nomesede');
            $sede->indirizzo = post('indirizzo');
            $sede->citta = post('citta');
            $sede->cap = post('cap');
            $sede->provincia = strtoupper(post('provincia'));
            $sede->km = post('km');
            $sede->id_nazione = !empty(post('id_nazione')) ? post('id_nazione') : null;
            $sede->idzona = !empty(post('idzona')) ? post('idzona') : 0;
            $sede->cellulare = post('cellulare');
            $sede->telefono = post('telefono');
            $sede->email = post('email');
            $sede->enable_newsletter = empty($opt_out_newsletter);
            $sede->codice_destinatario = post('codice_destinatario');
            $sede->is_automezzo = post('is_automezzo');
            $sede->is_rappresentante_fiscale = post('is_rappresentante_fiscale');
            $sede->targa = post('targa');
            $sede->nome = post('nome');
            $sede->descrizione = post('descrizione');
            $sede->save();

            $id_record = $sede->id;

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

        $sede->nomesede = post('nomesede');
        $sede->indirizzo = post('indirizzo');
        $sede->citta = post('citta');
        $sede->cap = post('cap');
        $sede->provincia = strtoupper(post('provincia'));
        $sede->km = post('km');
        $sede->id_nazione = !empty(post('id_nazione')) ? post('id_nazione') : null;
        $sede->idzona = !empty(post('idzona')) ? post('idzona') : 0;
        $sede->cellulare = post('cellulare');
        $sede->telefono = post('telefono');
        $sede->email = post('email');
        $sede->enable_newsletter = empty($opt_out_newsletter);
        $sede->codice_destinatario = post('codice_destinatario');
        $sede->is_automezzo = post('is_automezzo');
        $sede->is_rappresentante_fiscale = post('is_rappresentante_fiscale');
        $sede->targa = post('targa');
        $sede->nome = post('nome');
        $sede->descrizione = post('descrizione');
        $sede->gaddress = post('gaddress');
        $sede->lat = post('lat');
        $sede->lng = post('lng');
        $sede->save();

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
