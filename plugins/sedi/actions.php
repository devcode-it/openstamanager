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
            $sede->enable_newsletter = post('enable_newsletter_add');
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
        $sede = Sede::find($id_record);

        $sede->nomesede = post('nomesede');
        $sede->indirizzo = post('indirizzo');
        $sede->citta = post('citta');
        $sede->cap = post('cap');
        $sede->provincia = strtoupper(post('provincia'));
        $sede->id_nazione = !empty(post('id_nazione')) ? post('id_nazione') : null;
        $sede->telefono = post('telefono');
        $sede->cellulare = post('cellulare');
        $sede->email = post('email');
        $sede->enable_newsletter = post('enable_newsletter');
        $sede->codice_destinatario = post('codice_destinatario');
        $sede->is_rappresentante_fiscale = post('is_rappresentante_fiscale');
        $sede->piva = post('piva');
        $sede->codice_fiscale = post('codice_fiscale');
        $sede->is_automezzo = post('is_automezzo');
        $sede->idzona = !empty(post('idzona')) ? post('idzona') : 0;
        $sede->km = post('km');
        $sede->note = post('note');
        $sede->gaddress = post('gaddress');
        $sede->lat = post('lat');
        $sede->lng = post('lng');
        $sede->targa = post('targa');
        $sede->nome = post('nome');
        $sede->descrizione = post('descrizione');
        $sede->save();

        // Salva le tariffe specifiche per la sede
        salvaTariffeSede($id_record, $id_parent);

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
        $dbo->query('UPDATE `an_sedi` SET deleted_at = NOW() WHERE `id` = '.prepare($id));

        flash()->info(tr('Sede eliminata!'));

        break;
}

// Funzione per salvare le tariffe della sede
function salvaTariffeSede($id_sede, $id_parent)
{
    global $dbo;

    $costo_ore = (array) post('costo_ore');
    $costo_km = (array) post('costo_km');
    $costo_dirittochiamata = (array) post('costo_dirittochiamata');
    $tariffa_attiva = (array) post('tariffa_attiva');

    // Verifica se l'anagrafica è di tipo Cliente
    $anagrafica = Anagrafica::find($id_parent);
    if (!$anagrafica->isTipo('Cliente')) {
        return;
    }

    // Recupera i tipi di intervento
    $tipi_interventi = $dbo->fetchArray('SELECT id FROM in_tipiintervento WHERE deleted_at IS NULL');

    // Recupera le tariffe esistenti per questa sede
    $tariffe_esistenti = $dbo->fetchArray('SELECT id, idtipointervento FROM in_tariffe_sedi WHERE idsede = '.prepare($id_sede));
    $tariffe_map = [];
    foreach ($tariffe_esistenti as $tariffa) {
        $tariffe_map[$tariffa['idtipointervento']] = $tariffa['id'];
    }

    // Salva le tariffe
    foreach ($tipi_interventi as $tipo) {
        $id_tipo = $tipo['id'];
        $attivo = $tariffa_attiva[$id_tipo];

        if ($attivo) {
            // Se attivo, salva o aggiorna la tariffa
            if (isset($tariffe_map[$id_tipo])) {
                $dbo->update('in_tariffe_sedi', [
                    'costo_ore' => $costo_ore[$id_tipo],
                    'costo_km' => $costo_km[$id_tipo],
                    'costo_dirittochiamata' => $costo_dirittochiamata[$id_tipo],
                ], [
                    'id' => $tariffe_map[$id_tipo],
                ]);
            } else {
                $dbo->insert('in_tariffe_sedi', [
                    'idsede' => $id_sede,
                    'idtipointervento' => $id_tipo,
                    'costo_ore' => $costo_ore[$id_tipo],
                    'costo_km' => $costo_km[$id_tipo],
                    'costo_dirittochiamata' => $costo_dirittochiamata[$id_tipo],
                ]);
            }
        } else {
            // Se disattivo, elimina la tariffa se esiste
            if (isset($tariffe_map[$id_tipo])) {
                $dbo->query('DELETE FROM in_tariffe_sedi WHERE id = '.prepare($tariffe_map[$id_tipo]));
            }
        }
    }
}
