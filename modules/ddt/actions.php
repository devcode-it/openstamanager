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

use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\DDT\Components\Articolo;
use Modules\DDT\Components\Descrizione;
use Modules\DDT\Components\Riga;
use Modules\DDT\Components\Sconto;
use Modules\DDT\DDT;
use Modules\DDT\Stato;
use Modules\DDT\Tipo;

$module = Modules::get($id_module);

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

switch (filter('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $data = post('data');
        $id_tipo = post('idtipoddt');
        $id_segment = post('id_segment');

        $anagrafica = Anagrafica::find($idanagrafica);
        $tipo = Tipo::find($id_tipo);

        $ddt = DDT::build($anagrafica, $tipo, $data, $id_segment);
        $id_record = $ddt->id;

        $ddt->idcausalet = post('idcausalet');
        $ddt->save();

        flash()->info(tr('Aggiunto ddt in _TYPE_ numero _NUM_!', [
            '_TYPE_' => $dir,
            '_NUM_' => $ddt->numero,
        ]));

        break;

    case 'update':
        if (isset($id_record)) {
            $idstatoddt = post('idstatoddt');
            $idpagamento = post('idpagamento');
            $numero_esterno = post('numero_esterno');
            $id_anagrafica = post('idanagrafica');

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

            $ddt->data = post('data');
            $ddt->numero_esterno = $numero_esterno;
            $ddt->note = post('note');
            $ddt->note_aggiuntive = post('note_aggiuntive');

            $ddt->idstatoddt = $idstatoddt;
            $ddt->idpagamento = $idpagamento;
            $ddt->idconto = post('idconto');
            $ddt->idanagrafica = $id_anagrafica;
            $ddt->idreferente = post('idreferente');
            $ddt->idagente = post('idagente');
            $ddt->idspedizione = post('idspedizione');
            $ddt->idcausalet = post('idcausalet');
            $ddt->idsede_partenza = post('idsede_partenza');
            $ddt->idsede_destinazione = post('idsede_destinazione');
            $ddt->idvettore = post('idvettore');
            $ddt->data_ora_trasporto = post('data_ora_trasporto') ?: null;
            $ddt->idporto = post('idporto');
            $ddt->idaspettobeni = post('idaspettobeni');
            $ddt->idrivalsainps = $idrivalsainps;
            $ddt->idritenutaacconto = $idritenutaacconto;

            $ddt->n_colli = post('n_colli');
            $ddt->peso = post('peso');
            $ddt->volume = post('volume');
            $ddt->peso_manuale = post('peso_manuale');
            $ddt->volume_manuale = post('volume_manuale');
            $ddt->bollo = 0;
            $ddt->rivalsainps = 0;
            $ddt->ritenutaacconto = 0;

            $ddt->id_documento_fe = post('id_documento_fe');
            $ddt->codice_cup = post('codice_cup');
            $ddt->codice_cig = post('codice_cig');
            $ddt->num_item = post('num_item');

            $ddt->setScontoFinale(post('sconto_finale'), post('tipo_sconto_finale'));

            $ddt->save();

            $query = 'SELECT descrizione FROM dt_statiddt WHERE id='.prepare($idstatoddt);
            $rs = $dbo->fetchArray($query);

            // Ricalcolo inps, ritenuta e bollo (se l'ddt non è stato evaso)
            if ($dir == 'entrata') {
                if ($rs[0]['descrizione'] != 'Pagato') {
                    ricalcola_costiagg_ddt($id_record);
                }
            } else {
                if ($rs[0]['descrizione'] != 'Pagato') {
                    ricalcola_costiagg_ddt($id_record, $idrivalsainps, $idritenutaacconto, $bollo);
                }
            }

            aggiorna_sedi_movimenti('ddt', $id_record);

            // Controllo sulla presenza di DDT con lo stesso numero secondario
            $direzione = $ddt->direzione;
            if ($direzione == 'uscita' and !empty($numero_esterno)) {
                $count = DDT::where('numero_esterno', $numero_esterno)
                    ->where('id', '!=', $id_record)
                    ->where('idanagrafica', '=', $id_anagrafica)
                    ->whereHas('tipo', function ($query) use ($direzione) {
                        $query->where('dir', '=', $direzione);
                    })->count();
                if (!empty($count)) {
                    flash()->warning(tr('Esiste già un DDT con lo stesso numero secondario e la stessa anagrafica collegata!'));
                }
            }

            flash()->info(tr('Ddt modificato correttamente!'));
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
            $articolo = Articolo::build($ddt, $originale);
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
            $articolo = Articolo::build($ddt, $originale);
            $articolo->id_dettaglio_fornitore = post('id_dettaglio_fornitore') ?: null;
        }

        $articolo->descrizione = post('descrizione');
        $articolo->note = post('note');
        $articolo->um = post('um') ?: null;

        $articolo->costo_unitario = post('costo_unitario') ?: 0;
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

        if (post('idriga') != null) {
            flash()->info(tr('Articolo modificato!'));
        } else {
            flash()->info(tr('Articolo aggiunto!'));
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_ddt($id_record);

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($ddt);
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
        ricalcola_costiagg_ddt($id_record);

        break;

    case 'manage_riga':
        if (post('idriga') != null) {
            $riga = Riga::find(post('idriga'));
        } else {
            $riga = Riga::build($ddt);
        }

        $riga->descrizione = post('descrizione');
        $riga->note = post('note');
        $riga->um = post('um') ?: null;

        $riga->costo_unitario = post('costo_unitario') ?: 0;
        $riga->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $riga->setSconto(post('sconto'), post('tipo_sconto'));
        if ($dir == 'entrata') {
            $riga->setProvvigione(post('provvigione'), post('tipo_provvigione'));
        }

        $riga->qta = post('qta');

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga modificata!'));
        } else {
            flash()->info(tr('Riga aggiunta!'));
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_ddt($id_record);

        break;

    case 'manage_descrizione':
        if (post('idriga') != null) {
            $riga = Descrizione::find(post('idriga'));
        } else {
            $riga = Descrizione::build($ddt);
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

    // Aggiunta di un documento in ddt
    case 'add_ordine':
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

        // Creazione del ddt al volo
        if (post('create_document') == 'on') {
            $tipo = Tipo::where('dir', $documento->direzione)->first();

            $ddt = DDT::build($documento->anagrafica, $tipo, post('data'), post('id_segment'));
            $ddt->idpagamento = $documento->idpagamento;

            $ddt->id_documento_fe = $documento->id_documento_fe;
            $ddt->codice_cup = $documento->codice_cup;
            $ddt->codice_cig = $documento->codice_cig;
            $ddt->num_item = $documento->num_item;
            $ddt->idsede_destinazione = $id_sede;

            $ddt->idcausalet = post('id_causale_trasporto');
            $ddt->idreferente = $documento->idreferente;
            $ddt->idagente = $documento->idagente;

            $ddt->save();

            $id_record = $ddt->id;
        }

        if (!empty($documento->sconto_finale)) {
            $ddt->sconto_finale = $documento->sconto_finale;
        } elseif (!empty($documento->sconto_finale_percentuale)) {
            $ddt->sconto_finale_percentuale = $documento->sconto_finale_percentuale;
        }

        $ddt->save();

        $evadi_qta_parent = true;
        if ($documento->tipo->descrizione=='Ddt in uscita' || $documento->tipo->descrizione=='Ddt in entrata') {
            $evadi_qta_parent = false;
        }

        $righe = $documento->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on' and !empty(post('qta_da_evadere')[$riga->id])) {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($ddt, $qta, $evadi_qta_parent);

                // Aggiornamento seriali dalla riga dell'ordine
                if ($copia->isArticolo()) {
                    if ($documento->tipo->descrizione=='Ddt in uscita' || $documento->tipo->descrizione=='Ddt in entrata') {
                        // TODO: estrarre il listino corrispondente se presente
                        $originale = ArticoloOriginale::find($riga->idarticolo);

                        $prezzo = $documento->tipo->descrizione=='Ddt in entrata' ? $originale->prezzo_vendita : $originale->prezzo_acquisto;
                        $id_iva = $originale->idiva_vendita ? $originale->idiva_vendita : setting('Iva predefinita');

                        $copia->setPrezzoUnitario($prezzo, $id_iva);
                    }
                    $serials = is_array(post('serial')[$riga->id]) ? post('serial')[$riga->id] : [];

                    $copia->serials = $serials;
                }

                $copia->save();
            }
        }

        // Modifica finale dello stato
        if (post('create_document') == 'on') {
            $ddt->idstatoddt = post('id_stato');
            $ddt->save();
        }

        ricalcola_costiagg_ddt($id_record);

        // Messaggio informativo
        $message = tr('_DOC_ aggiunto!', [
            '_DOC_' => $documento->getReference(),
        ]);
        flash()->info($message);

        break;

    // Eliminazione riga
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

        ricalcola_costiagg_ddt($id_record);
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
            $new_riga->setDocument($ddt);
            $new_riga->qta_evasa = 0;
            $new_riga->save();

            if ($new_riga->isArticolo()) {
                $new_riga->movimenta($new_riga->qta);
            }

            $riga = null;
        }

        flash()->info(tr('Righe duplicate!'));

        break;

    // eliminazione ddt
    case 'delete':
        try {
            // Se il ddt è collegato ad un ddt di trasporto interno, devo annullare il movimento del magazzino
            if ($ddt->id_ddt_trasporto_interno !== null) {
                $ddt_trasporto = DDT::find($ddt->id_ddt_trasporto_interno);
                // prendo le righe del ddt di trasporto
                $righe_trasporto = $ddt_trasporto->getRighe();

                // per ogni riga del ddt di trasporto movimento il magazzino con la quantità negativa
                foreach ($righe_trasporto as $riga_trasporto) {
                    $riga_trasporto->movimenta(-$riga_trasporto->qta);
                }
            }
            
            $ddt->delete();

            flash()->info(tr('Ddt eliminato!'));
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
            $dbo->query('UPDATE `dt_righe_ddt` SET `order` = '.prepare($i + 1).' WHERE id='.prepare($id_riga));
        }

        break;

    /*
     * Gestione della generazione di DDT in direzione opposta a quella corrente, per completare il riferimento di trasporto interno tra sedi distinte dell'anagrafica Azienda.
     */
    case 'completa_trasporto':
        $tipo = Tipo::where('dir', '!=', $ddt->direzione)->first();
        $stato = Stato::where('descrizione', '=', 'Evaso')->first();

       // Duplicazione DDT
        $id_segment = post('id_segment');
        if (get('id_segment')) {
            $id_segment = get('id_segment');
        }

        $copia = DDT::build($ddt->anagrafica, $tipo, $ddt->data, $id_segment);
        $copia->stato()->associate($stato);
        $copia->id_ddt_trasporto_interno = $ddt->id;
        $copia->idaspettobeni = $ddt->idaspettobeni;
        $copia->idcausalet = $ddt->idcausalet;
        $copia->idspedizione = $ddt->idspedizione;
        $copia->n_colli = $ddt->n_colli;
        $copia->idpagamento = $ddt->idpagamento;
        $copia->idporto = $ddt->idporto;
        $copia->idvettore = $ddt->idvettore;
        $copia->data_ora_trasporto = $ddt->data_ora_trasporto;
        $copia->idsede_partenza = $ddt->idsede_partenza;
        $copia->idsede_destinazione = $ddt->idsede_destinazione;

        $copia->save();

        // Copia righe
        $righe = $ddt->getRighe();
        foreach ($righe as $riga) {
            $copia_riga = $riga->replicate();
            $copia_riga->setDocument($copia);

            // Aggiornamento riferimenti
            $copia_riga->idddt = $copia->id;
            $copia_riga->original_id = null;
            $copia_riga->original_type = null;

            $copia_riga->save();

            // Movimentazione forzata in direzione del documento
            if ($copia_riga->isArticolo()) {
                $copia_riga->movimenta($copia_riga->qta);
            }
        }

        // Salvataggio riferimento
        $ddt->id_ddt_trasporto_interno = $copia->id;
        $ddt->save();

        $id_record = $copia->id;
        $id_module = $ddt->direzione == 'entrata' ? Module::pool('Ddt di acquisto')->id : Module::pool('Ddt di vendita')->id;

        break;

    // Duplica ddt
    case 'copy':
        $new = $ddt->replicate();

        $new->numero = DDT::getNextNumero($new->data, $dir, $id_segment);
        $new->numero_esterno = DDT::getNextNumeroSecondario($new->data, $dir, $new->id_segment);

        $stato = Stato::where('descrizione', '=', 'Bozza')->first();
        $new->stato()->associate($stato);
        $new->save();

        $id_record = $new->id;

        $righe = $ddt->getRighe();
        foreach ($righe as $riga) {
            $new_riga = $riga->replicate();
            $new_riga->setDocument($new);

            $new_riga->qta_evasa = 0;
            $new_riga->idordine = 0;
            $new_riga->save();

            if ($new_riga->isArticolo()) {
                $new_riga->movimenta($new_riga->qta);
            }
        }

        flash()->info(tr('DDT duplicato correttamente!'));

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
                $articolo = Articolo::build($ddt, $originale);
                $qta = 1;

                $articolo->descrizione = $originale->descrizione;
                $articolo->um = $originale->um;
                $articolo->qta = 1;
                $articolo->costo_unitario = $originale->prezzo_acquisto;

                $id_iva = $originale->idiva_vendita ?: setting('Iva predefinita');
                $id_anagrafica = $ddt->idanagrafica;
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

                $articolo->setPrezzoUnitario($prezzo_unitario, $id_iva);
                $articolo->setSconto($sconto, 'PRC');
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

// Aggiornamento stato degli ordini presenti in questa fattura in base alle quantità totali evase
if (!empty($id_record) && setting('Cambia automaticamente stato ordini fatturati')) {
    $rs = $dbo->fetchArray('SELECT idordine FROM dt_righe_ddt WHERE idddt='.prepare($id_record).' AND idordine!=0');

    for ($i = 0; $i < sizeof($rs); ++$i) {
        $dbo->query('UPDATE or_ordini SET idstatoordine=(SELECT id FROM or_statiordine WHERE descrizione="'.get_stato_ordine($rs[$i]['idordine']).'") WHERE id = '.prepare($rs[$i]['idordine']));
    }
}
