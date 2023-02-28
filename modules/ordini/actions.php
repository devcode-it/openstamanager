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
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Ordini\Components\Articolo;
use Modules\Ordini\Components\Descrizione;
use Modules\Ordini\Components\Riga;
use Modules\Ordini\Components\Sconto;
use Modules\Ordini\Ordine;
use Modules\Ordini\Tipo;
use Modules\Preventivi\Preventivo;
use Plugins\ListinoClienti\DettaglioPrezzo;

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
        $id_segment = post('id_segment');

        $anagrafica = Anagrafica::find($idanagrafica);
        $tipo = Tipo::where('dir', $dir)->first();

        $ordine = Ordine::build($anagrafica, $tipo, $data, $id_segment);
        $id_record = $ordine->id;

        flash()->info(tr('Aggiunto ordine numero _NUM_!', [
            '_NUM_' => $ordine->numero,
        ]));

        break;

    case 'update':
        if (isset($id_record)) {
            $idstatoordine = post('idstatoordine');
            $idpagamento = post('idpagamento');
            $idsede = post('idsede');

            $totale_imponibile = get_imponibile_ordine($id_record);
            $totale_ordine = get_totale_ordine($id_record);

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

            $ordine->idanagrafica = post('idanagrafica');
            $ordine->idreferente = post('idreferente');
            $ordine->data = post('data') ?: null;
            $ordine->numero = post('numero');
            $ordine->numero_esterno = post('numero_esterno');
            $ordine->note = post('note');
            $ordine->note_aggiuntive = post('note_aggiuntive');

            $ordine->idagente = post('idagente');
            $ordine->idstatoordine = $idstatoordine;
            $ordine->idpagamento = $idpagamento;
            $ordine->idsede = $idsede;
            $ordine->idconto = post('idconto');
            $ordine->idrivalsainps = $idrivalsainps;
            $ordine->idritenutaacconto = $idritenutaacconto;

            $ordine->bollo = 0;
            $ordine->rivalsainps = 0;
            $ordine->ritenutaacconto = 0;

            $ordine->numero_cliente = post('numero_cliente');
            $ordine->data_cliente = post('data_cliente') ?: null;

            $ordine->id_documento_fe = post('numero_cliente');
            $ordine->codice_commessa = post('codice_commessa');
            $ordine->codice_cup = post('codice_cup');
            $ordine->codice_cig = post('codice_cig');
            $ordine->num_item = post('num_item');
            $ordine->condizioni_fornitura = post('condizioni_fornitura');

            $ordine->setScontoFinale(post('sconto_finale'), post('tipo_sconto_finale'));

            $ordine->save();

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
            $articolo->confermato = $dir == 'entrata' ? setting('Conferma automaticamente le quantità negli ordini cliente') : setting('Conferma automaticamente le quantità negli ordini fornitore');

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
        $articolo->note = post('note');
        $articolo->um = post('um') ?: null;

        $articolo->costo_unitario = post('costo_unitario') ?: 0;
        $articolo->data_evasione = post('data_evasione') ?: null;
        $articolo->ora_evasione = post('ora_evasione') ?: null;
        $articolo->confermato = post('confermato') ?: 0;
        $articolo->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $articolo->setSconto(post('sconto'), post('tipo_sconto'));
        if ($dir == 'entrata') {
            $articolo->setProvvigione(post('provvigione'), post('tipo_provvigione'));
        }

        try {
            $articolo->qta = post('qta');
        } catch (UnexpectedValueException $e) {
            flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
        }

        $articolo->save();

        // Impostare data evasione su tutte le righe
        if (post('data_evasione_all') == 1) {
            $righe = $ordine->getRighe()->where('is_descrizione', '=', '0');

            foreach ($righe as $riga) {
                $riga->data_evasione = post('data_evasione') ?: null;
                $riga->ora_evasione = post('ora_evasione') ?: null;
                $riga->save();
            }
        }
        // Impostare confermato su tutte le righe
        if (post('confermato_all') == 1) {
            $righe = $ordine->getRighe()->where('is_descrizione', '=', '0');

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
        $sconto->note = post('note');
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
        $riga->note = post('note');
        $riga->um = post('um') ?: null;

        $riga->costo_unitario = post('costo_unitario') ?: 0;
        $riga->data_evasione = post('data_evasione') ?: null;
        $riga->ora_evasione = post('ora_evasione') ?: null;
        $riga->confermato = post('confermato') ?: 0;
        $riga->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $riga->setSconto(post('sconto'), post('tipo_sconto'));
        if ($dir == 'entrata') {
            $riga->setProvvigione(post('provvigione'), post('tipo_provvigione'));
        }

        $riga->qta = post('qta');

        $riga->save();

        // Impostare data evasione su tutte le righe
        if (post('data_evasione_all') == 1) {
            $righe = $ordine->getRighe()->where('is_descrizione', '=', '0');

            foreach ($righe as $riga) {
                $riga->data_evasione = post('data_evasione') ?: null;
                $riga->ora_evasione = post('ora_evasione') ?: null;
                $riga->save();
            }
        }
        // Impostare confermato su tutte le righe
        if (post('confermato_all') == 1) {
            $righe = $ordine->getRighe()->where('is_descrizione', '=', '0');

            foreach ($righe as $riga) {
                $riga->confermato = post('confermato') ?: 0;
                $riga->save();
            }
        }

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
        $riga->note = post('note');
        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga descrittiva modificata!'));
        } else {
            flash()->info(tr('Riga descrittiva aggiunta!'));
        }

        break;

    // Scollegamento riga generica da ordine
    case 'delete_riga':
        $id_righe = (array)post('righe');
        
        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Descrizione::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            try {
                $riga->delete();
            } catch (InvalidArgumentException $e) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
            }

            $riga = null;
        }

        ricalcola_costiagg_ordine($id_record);

        flash()->info(tr('Righe eliminate!'));

        break;

    // Duplicazione riga
    case 'copy_riga':
        $id_righe = (array)post('righe');
        
        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Descrizione::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            $new_riga = $riga->replicate();
            $new_riga->setDocument($ordine);
            $new_riga->qta_evasa = 0;
            $new_riga->save();

            $riga = null;
        }

        flash()->info(tr('Righe duplicate!'));

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
            $dbo->query('UPDATE `or_righe_ordini` SET `order` = '.prepare($i + 1).' WHERE id='.prepare($id_riga));
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

            $ordine = Ordine::build($documento->anagrafica, $tipo, post('data'), post('id_segment'));
            $ordine->idpagamento = $documento->idpagamento;
            $ordine->idsede = $id_sede;

            $ordine->id_documento_fe = $documento->id_documento_fe;
            $ordine->numero_cliente = $documento->id_documento_fe;
            $ordine->codice_cup = $documento->codice_cup;
            $ordine->codice_cig = $documento->codice_cig;
            $ordine->num_item = $documento->num_item;
            $ordine->idreferente = $documento->idreferente;
            $ordine->idagente = $documento->idagente;

            $ordine->save();

            $id_record = $ordine->id;
        }

        if (!empty($documento->sconto_finale)) {
            $ordine->sconto_finale = $documento->sconto_finale;
        } elseif (!empty($documento->sconto_finale_percentuale)) {
            $ordine->sconto_finale_percentuale = $documento->sconto_finale_percentuale;
        }

        $ordine->save();

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

    // Aggiunta di un ordine fornitore da un ordine cliente
    case 'add_ordine_cliente':
        $ordine_cliente = Ordine::find(post('id_documento'));

        // Creazione dell' ordine al volo
        if (post('create_document') == 'on') {
            $anagrafica = Anagrafica::find(post('idanagrafica'));
            $tipo = Tipo::where('dir', $dir)->first();

            $ordine = Ordine::build($anagrafica, $tipo, post('data'), post('id_segment'));
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

                // Impostazione al prezzo di acquisto per Articoli
                if ($copia->isArticolo()) {
                    $copia->setSconto(0, 'PRC');

                    $articolo = $copia->articolo;

                    $fornitore = DettaglioPrezzo::dettagli($riga->idarticolo, $anagrafica->id, $dir, $qta)->first();
                    if (empty($fornitore)) {
                        $fornitore = DettaglioPrezzo::dettaglioPredefinito($riga->idarticolo, $anagrafica->id, $dir)->first();
                    }

                    $prezzo_unitario = $fornitore->prezzo_unitario - ($fornitore->prezzo_unitario * $fornitore->percentuale / 100);

                    $copia->setPrezzoUnitario($fornitore ? $prezzo_unitario : $articolo->prezzo_acquisto, $copia->aliquota->id);
                    $copia->setSconto($fornitore->sconto_percentuale ?: 0, 'PRC');
                }

                $copia->save();
            }
        }

        // Modifica finale dello stato
        if (post('create_document') == 'on') {
            $ordine->idstatoordine = post('id_stato');
            $ordine->save();
        }

        ricalcola_costiagg_ordine($id_record);

        flash()->info(tr('Ordine _NUM_ aggiunto!', [
            '_NUM_' => $ordine->numero,
        ]));

        break;

    // Aggiunta di un ordine fornitore da un preventivo
    case 'add_ordine_fornitore':
        $preventivo = Preventivo::find(post('id_documento'));

        // Creazione dell' ordine al volo
        if (post('create_document') == 'on') {
            $anagrafica = Anagrafica::find(post('idanagrafica'));
            $tipo = Tipo::where('dir', $dir)->first();

            $ordine = Ordine::build($anagrafica, $tipo, post('data'), post('id_segment'));
            $ordine->save();

            $id_record = $ordine->id;
        }

        $righe = $preventivo->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on' and !empty(post('qta_da_evadere')[$riga->id])) {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($ordine, $qta, false);
                $copia->save();

                // Ripristino dei valori di default per campi potenzialmente impostati
                $copia->original_id = null;
                $copia->original_type = null;
                $copia->qta = $qta;
                $copia->qta_evasa = 0;
                $copia->costo_unitario = 0;
                $copia->data_evasione = null;
                $copia->ora_evasione = null;
                $copia->confermato = setting('Conferma automaticamente le quantità negli ordini fornitore');

                // Impostazione al prezzo di acquisto per Articoli
                if ($copia->isArticolo()) {
                    $copia->setSconto(0, 'PRC');

                    $articolo = $copia->articolo;

                    $fornitore = DettaglioPrezzo::dettagli($riga->idarticolo, $anagrafica->id, $dir, $qta)->first();
                    if (empty($fornitore)) {
                        $fornitore = DettaglioPrezzo::dettaglioPredefinito($riga->idarticolo, $anagrafica->id, $dir)->first();
                    }

                    $prezzo_unitario = $fornitore->prezzo_unitario - ($fornitore->prezzo_unitario * $fornitore->percentuale / 100);

                    $copia->setPrezzoUnitario($fornitore ? $prezzo_unitario : $articolo->prezzo_acquisto, $copia->aliquota->id);
                    $copia->setSconto($fornitore->sconto_percentuale ?: 0, 'PRC');
                }

                $copia->save();
            }
        }

        // Modifica finale dello stato
        if (post('create_document') == 'on') {
            $ordine->idstatoordine = post('id_stato');
            $ordine->save();
        }

        ricalcola_costiagg_ordine($id_record);

        flash()->info(tr('Ordine _NUM_ aggiunto!', [
            '_NUM_' => $ordine->numero,
        ]));

        break;

    case 'add_articolo':
        $id_articolo = post('id_articolo');
        $barcode = post('barcode');

        if (!empty($barcode)) {
            $id_articolo = $dbo->selectOne('mg_articoli', 'id',  ['deleted_at' => null, 'barcode' => $barcode])['id'];
        }

        if (!empty($id_articolo)) {
            $permetti_movimenti_sotto_zero = setting('Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita');
            $qta_articolo = $dbo->selectOne('mg_articoli', 'qta', ['id' => $id_articolo])['qta'];

            $originale = ArticoloOriginale::find($id_articolo);

            if ($qta_articolo <= 0 && !$permetti_movimenti_sotto_zero && !$originale->servizio && $dir == 'entrata') {
                $response['error'] = tr('Quantità a magazzino non sufficiente');
                echo json_encode($response);
            } else {
                $articolo = Articolo::build($ordine, $originale);
                $qta = 1;

                $articolo->descrizione = $originale->descrizione;
                $articolo->um = $originale->um;
                $articolo->qta = 1;
                $articolo->costo_unitario = $originale->prezzo_acquisto;

                $id_iva = $originale->idiva_vendita ?: setting('Iva predefinita');
                $id_anagrafica = $ordine->idanagrafica;
                $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
        
                // CALCOLO PREZZO UNITARIO
                $prezzo_unitario = 0;
                $sconto = 0;
                // Prezzi netti clienti / listino fornitore
                $prezzi = $dbo->fetchArray('SELECT minimo, massimo, sconto_percentuale, '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario
                FROM mg_prezzi_articoli
                WHERE id_articolo = '.prepare($id_articolo).' AND dir = '.prepare($dir).' AND id_anagrafica = '.prepare($id_anagrafica));

                if ($prezzi) {
                    foreach ($prezzi as $prezzo) {
                        if ($qta >= $prezzo['minimo'] && $qta <= $prezzo['massimo']) {
                            $prezzo_unitario = $prezzo['prezzo_unitario'];
                            $sconto = $prezzo['sconto_percentuale'];
                            continue;
                        }

                        if ($prezzo['minimo'] == null && $prezzo['massimo'] == null && $prezzo['prezzo_unitario'] != null) {
                            $prezzo_unitario = $prezzo['prezzo_unitario'];
                            $sconto = $prezzo['sconto_percentuale'];
                            continue;
                        }
                    }
                } 
                if (empty($prezzo_unitario)) {
                    // Prezzi listini clienti
                    $listino = $dbo->fetchOne('SELECT sconto_percentuale AS sconto_percentuale_listino, '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario_listino
                    FROM mg_listini
                    LEFT JOIN mg_listini_articoli ON mg_listini.id=mg_listini_articoli.id_listino
                    LEFT JOIN an_anagrafiche ON mg_listini.id=an_anagrafiche.id_listino
                    WHERE mg_listini.data_attivazione<=NOW() AND mg_listini_articoli.data_scadenza>=NOW() AND mg_listini.attivo=1 AND id_articolo = '.prepare($id_articolo).' AND dir = '.prepare($dir).' AND idanagrafica = '.prepare($id_anagrafica));

                    if ($listino) {
                        $prezzo_unitario = $listino['prezzo_unitario_listino'];
                        $sconto = $listino['sconto_percentuale_listino'];
                    }
                }
                $prezzo_unitario = $prezzo_unitario ?: ($prezzi_ivati ? $originale->prezzo_vendita_ivato : $originale->prezzo_vendita);
                $provvigione = $dbo->selectOne('an_anagrafiche', 'provvigione_default', ['idanagrafica' => $ordine->idagente])['provvigione_default'];

                $articolo->setPrezzoUnitario($prezzo_unitario, $id_iva);
                $articolo->setSconto($sconto, 'PRC');
                $articolo->setProvvigione($provvigione ?: 0, 'PRC');
                $articolo->save();

                
                flash()->info(tr('Nuovo articolo aggiunto!'));
            }
        } else {
            $response['error'] = tr('Nessun articolo corrispondente a magazzino');
            echo json_encode($response);
        }

        break;

    case 'update_inline':
        $id_riga = post('riga_id');
        $riga = $riga ?: Riga::find($id_riga);
        $riga = $riga ?: Articolo::find($id_riga);

        if (!empty($riga)) {
            $riga->qta = post('qta');
            $riga->setSconto(post('sconto'), post('tipo_sconto'));
            $riga->save();

            flash()->info(tr('Quantità aggiornata!'));
        }

        break;
}
