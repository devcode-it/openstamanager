<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Ordini\Components\Articolo;
use Modules\Ordini\Components\Descrizione;
use Modules\Ordini\Components\Riga;
use Modules\Ordini\Components\Sconto;
use Modules\Ordini\Ordine;
use Modules\Ordini\Tipo;

$module = Modules::get($id_module);

if ($module['name'] == 'Ordini cliente') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $data = post('data');

        $anagrafica = Anagrafica::find($idanagrafica);
        $tipo = Tipo::where('dir', $dir)->first();

        $ordine = Ordine::build($anagrafica, $tipo, $data);
        $id_record = $ordine->id;

        flash()->info(tr('Aggiunto ordine numero _NUM_!', [
            '_NUM_' => $ordine->numero,
        ]));

        break;

    case 'update':
        $idstatoordine = post('idstatoordine');
        $idpagamento = post('idpagamento');
        $idsede = post('idsede');

        $totale_imponibile = get_imponibile_ordine($id_record);
        $totale_ordine = get_totale_ordine($id_record);

        $tipo_sconto = post('tipo_sconto_generico');
        $sconto = post('sconto_generico');

        if ($dir == 'uscita') {
            $idrivalsainps = post('id_rivalsa_inps');
            $idritenutaacconto = post('id_ritenuta_acconto');
            $bollo = post('bollo');
        } else {
            $idrivalsainps = 0;
            $idritenutaacconto = 0;
            $bollo = 0;
        }

        // Leggo la descrizione del pagamento
        $query = 'SELECT descrizione FROM co_pagamenti WHERE id='.prepare($idpagamento);
        $rs = $dbo->fetchArray($query);
        $pagamento = $rs[0]['descrizione'];

        // Query di aggiornamento
        $dbo->update('or_ordini', [
            'idanagrafica' => post('idanagrafica'),
            'data' => post('data'),
            'numero' => post('numero'),
            'numero_esterno' => post('numero_esterno'),
            'note' => post('note'),
            'note_aggiuntive' => post('note_aggiuntive'),

            'idagente' => post('idagente'),
            'idstatoordine' => $idstatoordine,
            'idpagamento' => $idpagamento,
            'idsede' => $idsede,
            'idconto' => post('idconto'),
            'idrivalsainps' => $idrivalsainps,
            'idritenutaacconto' => $idritenutaacconto,

            'bollo' => 0,
            'rivalsainps' => 0,
            'ritenutaacconto' => 0,

            'numero_cliente' => post('numero_cliente'),
            'data_cliente' => post('data_cliente'),

            'id_documento_fe' => post('id_documento_fe'),
            'codice_cup' => post('codice_cup'),
            'codice_cig' => post('codice_cig'),
            'num_item' => post('num_item'),
        ], ['id' => $id_record]);

        if ($dbo->query($query)) {
            $query = 'SELECT descrizione FROM or_statiordine WHERE id='.prepare($idstatoordine);
            $rs = $dbo->fetchArray($query);

            // Ricalcolo inps, ritenuta e bollo (se l'ordine non è stato evaso)
            if ($dir == 'entrata') {
                if ($rs[0]['descrizione'] != 'Evaso') {
                    ricalcola_costiagg_ordine($id_record);
                }
            } else {
                if ($rs[0]['descrizione'] != 'Evaso') {
                    ricalcola_costiagg_ordine($id_record, $idrivalsainps, $idritenutaacconto, $bollo);
                }
            }

            flash()->info(tr('Ordine modificato correttamente!'));
        }

        break;

    case 'manage_barcode':
        foreach (post('qta') as $id_articolo => $qta) {
            if ($id_articolo == '-id-') {
                continue;
            }

            // Dati di input
            $sconto = post('sconto')[$id_articolo];
            $tipo_sconto = post('tipo_sconto')[$id_articolo];
            $prezzo_unitario = post('prezzo_unitario')[$id_articolo];
            $id_dettaglio_fornitore = post('id_dettaglio_fornitore')[$id_articolo];
            $id_iva = $originale->idiva_vendita ? $originale->idiva_vendita : setting('Iva predefinita');

            // Creazione articolo
            $originale = ArticoloOriginale::find($id_articolo);
            $articolo = Articolo::build($ordine, $originale);
            $articolo->id_dettaglio_fornitore = $id_dettaglio_fornitore ?: null;

            $articolo->setPrezzoUnitario($prezzo_unitario, $id_iva);
            if ($dir == 'entrata') {
                $articolo->costo_unitario = $originale->prezzo_acquisto;
            }
            $articolo->setSconto($sconto, $tipo_sconto);
            $articolo->qta = $qta;

            $articolo->save();
        }

        flash()->info(tr('Articoli aggiunti!'));

        break;

    case 'manage_articolo':
        if (post('idriga') != null) {
            $articolo = Articolo::find(post('idriga'));
        } else {
            $originale = ArticoloOriginale::find(post('idarticolo'));
            $articolo = Articolo::build($ordine, $originale);
            $articolo->id_dettaglio_fornitore = post('id_dettaglio_fornitore') ?: null;
        }

        $articolo->descrizione = post('descrizione');
        $articolo->um = post('um') ?: null;

        $articolo->costo_unitario = post('costo_unitario') ?: 0;
        $articolo->data_evasione = post('data_evasione') ?: null;
        $articolo->confermato = post('confermato') ?: 0;
        $articolo->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $articolo->setSconto(post('sconto'), post('tipo_sconto'));

        try {
            $articolo->qta = post('qta');
        } catch (UnexpectedValueException $e) {
            flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
        }

        $articolo->save();

        // Impostare data evasione su tutte le righe
        if (post('data_evasione_all') == 1) {
            $righe = $ordine->getRighe();

            foreach ($righe as $riga) {
                $riga->data_evasione = post('data_evasione') ?: null;
                $riga->save();
            }
        }
        // Impostare confermato su tutte le righe
        if (post('confermato_all') == 1) {
            $righe = $ordine->getRighe();

            foreach ($righe as $riga) {
                $riga->confermato = post('confermato') ?: 0;
                $riga->save();
            }
        }

        if (post('idriga') != null) {
            flash()->info(tr('Articolo modificato!'));
        } else {
            flash()->info(tr('Articolo aggiunto!'));
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_ordine($id_record);

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($ordine);
        }

        $sconto->descrizione = post('descrizione');
        $sconto->setScontoUnitario(post('sconto_unitario'), post('idiva'));

        $sconto->save();

        if (post('idriga') != null) {
            flash()->info(tr('Sconto/maggiorazione modificato!'));
        } else {
            flash()->info(tr('Sconto/maggiorazione aggiunto!'));
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_ordine($id_record);

        break;

    case 'manage_riga':
        if (post('idriga') != null) {
            $riga = Riga::find(post('idriga'));
        } else {
            $riga = Riga::build($ordine);
        }

        $riga->descrizione = post('descrizione');
        $riga->um = post('um') ?: null;

        $riga->costo_unitario = post('costo_unitario') ?: 0;
        $riga->data_evasione = post('data_evasione') ?: null;
        $riga->confermato = post('confermato') ?: 0;
        $riga->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $riga->setSconto(post('sconto'), post('tipo_sconto'));

        $riga->qta = post('qta');

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga modificata!'));
        } else {
            flash()->info(tr('Riga aggiunta!'));
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_ordine($id_record);

        break;

    case 'manage_descrizione':
        if (post('idriga') != null) {
            $riga = Descrizione::find(post('idriga'));
        } else {
            $riga = Descrizione::build($ordine);
        }

        $riga->descrizione = post('descrizione');

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga descrittiva modificata!'));
        } else {
            flash()->info(tr('Riga descrittiva aggiunta!'));
        }

        break;

    // Scollegamento riga generica da ordine
    case 'delete_riga':
        $id_riga = post('riga_id');
        $type = post('riga_type');
        $riga = $ordine->getRiga($type, $id_riga);

        if (!empty($riga)) {
            try {
                $riga->delete();

                flash()->info(tr('Riga rimossa!'));
            } catch (InvalidArgumentException $e) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
            }
        }

        ricalcola_costiagg_ordine($id_record);

        break;

    // Eliminazione ordine
    case 'delete':
        try {
            $ordine->delete();

            flash()->info(tr('Ordine eliminato!'));
        } catch (InvalidArgumentException $e) {
            flash()->error(tr('Sono stati utilizzati alcuni serial number nel documento: impossibile procedere!'));
        }

        break;

    case 'add_serial':
        $articolo = Articolo::find(post('idriga'));

        $serials = (array) post('serial');
        $articolo->serials = $serials;

        break;

    case 'update_position':
        $order = explode(',', post('order', true));

        foreach ($order as $i => $id_riga) {
            $dbo->query('UPDATE `or_righe_ordini` SET `order` = '.prepare($i).' WHERE id='.prepare($id_riga));
        }

        break;

    // Aggiunta di un documento in ordine
    case 'add_preventivo':
    case 'add_documento':
        $class = post('class');
        $id_documento = post('id_documento');

        // Individuazione del documento originale
        if (!is_subclass_of($class, \Common\Document::class)) {
            return;
        }
        $documento = $class::find($id_documento);

        // Individuazione sede
        $id_sede = ($documento->direzione == 'entrata') ? $documento->idsede_destinazione : $documento->idsede_partenza;
        $id_sede = $id_sede ?: $documento->idsede;
        $id_sede = $id_sede ?: 0;

        // Creazione dell' ordine al volo
        if (post('create_document') == 'on') {
            $tipo = Tipo::where('dir', $documento->direzione)->first();

            $ordine = Ordine::build($documento->anagrafica, $tipo, post('data'));
            $ordine->idpagamento = $documento->idpagamento;
            $ordine->idsede = $id_sede;

            $ordine->id_documento_fe = $documento->id_documento_fe;
            $ordine->codice_cup = $documento->codice_cup;
            $ordine->codice_cig = $documento->codice_cig;
            $ordine->num_item = $documento->num_item;

            $ordine->save();

            $id_record = $ordine->id;
        }

        $righe = $documento->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on' and !empty(post('qta_da_evadere')[$riga->id])) {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($ordine, $qta);
                $copia->save();
            }
        }

        // Modifica finale dello stato
        if (post('create_document') == 'on') {
            $ordine->idstatoordine = post('id_stato');
            $ordine->save();
        }

        ricalcola_costiagg_ordine($id_record);

        // Messaggio informativo
        $message = tr('_DOC_ aggiunto!', [
            '_DOC_' => $documento->getReference(),
        ]);
        flash()->info($message);

        break;

    // Aggiunta di un ordine cliente in ordine fornitore
    case 'add_ordine_cliente':
        $ordine_cliente = Ordine::find(post('id_documento'));

        // Creazione dell' ordine al volo
        if (post('create_document') == 'on') {
            $anagrafica = Anagrafica::find(post('idanagrafica'));
            $tipo = Tipo::where('dir', $dir)->first();

            $ordine = Ordine::build($anagrafica, $tipo, post('data'));
            $ordine->save();

            $id_record = $ordine->id;
        }

        $righe = $ordine_cliente->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on' and !empty(post('qta_da_evadere')[$riga->id])) {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->replicate();
                $copia->setDocument($ordine);

                // Ripristino dei valori di default per campi potenzialmente impostati
                $copia->original_id = null;
                $copia->original_type = null;
                $copia->qta = $qta;
                $copia->qta_evasa = 0;
                $copia->costo_unitario = 0;
                $copia->setSconto(0, 'EUR');

                // Impostazione al prezzo di acquisto per Articoli
                if ($copia->isArticolo()) {
                    $articolo = $copia->articolo;
                    $fornitore = $articolo->dettaglioFornitore($anagrafica->id); // Informazioni del fornitore
                    $copia->setPrezzoUnitario($fornitore ? $fornitore->prezzo_acquisto : $articolo->prezzo_acquisto, $copia->aliquota->id);
                }

                $copia->save();
            }
        }

        ricalcola_costiagg_ordine($id_record);

        flash()->info(tr('Ordine _NUM_ aggiunto!', [
            '_NUM_' => $ordine->numero,
        ]));

        break;
}
