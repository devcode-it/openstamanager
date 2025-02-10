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

use Carbon\Carbon;
use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Iva\Aliquota;
use Modules\Ordini\Components\Articolo;
use Modules\Ordini\Components\Descrizione;
use Modules\Ordini\Components\Riga;
use Modules\Ordini\Components\Sconto;
use Modules\Ordini\Ordine;
use Modules\Ordini\Tipo;
use Modules\Preventivi\Preventivo;
use Plugins\ListinoClienti\DettaglioPrezzo;

$module = Module::find($id_module);

if ($module->name == 'Ordini cliente') {
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
        if (!empty($id_record)) {
            $idstatoordine = post('idstatoordine');
            $idpagamento = post('idpagamento');
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
            $query = 'SELECT `title` AS descrizione FROM `co_pagamenti` LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_pagamenti`.`id`='.prepare($idpagamento);
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
            $ordine->idspedizione = post('idspedizione');
            $ordine->idporto = post('idporto');
            $ordine->idvettore = post('idvettore');
            $ordine->idsede_partenza = post('idsede_partenza');
            $ordine->idsede_destinazione = post('idsede_destinazione');
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

            // Verifica la presenza di ordini con lo stesso numero
            $ordini = $dbo->fetchArray('SELECT * FROM `or_ordini` WHERE `numero_cliente`='.prepare(post('numero_cliente'))."AND `numero_cliente` IS NOT NULL AND `numero_cliente` != '' AND `id`!=".prepare($id_record).' AND `idanagrafica`='.prepare(post('idanagrafica'))." AND DATE_FORMAT(`or_ordini`.`data`, '%Y')=".prepare(Carbon::parse(post('data'))->copy()->format('Y')));

            if (!empty($ordini)) {
                $documento = '';
                foreach ($ordini as $rs) {
                    $descrizione = tr('Ordine cliente num. _NUM_ del _DATE_', [
                        '_NUM_' => !empty($rs['numero_esterno']) ? $rs['numero_esterno'] : $rs['numero'],
                        '_DATE_' => Translator::dateToLocale($rs['data']),
                    ]);

                    $documenti .= '<li>'.Modules::link('Ordini cliente', $rs['id'], $descrizione).'</li>';
                }

                flash()->error(tr('E\' già presente un ordine con numero _NUM_ <ul>_ORDINI_</ul>', [
                    '_NUM_' => post('numero_cliente'),
                    '_ORDINI_' => $documenti,
                ]));

                $ordine->numero_cliente = null;
                $ordine->id_documento_fe = null;
            }

            $ordine->setScontoFinale(post('sconto_finale'), post('tipo_sconto_finale'));

            $ordine->save();

            if ($dbo->query($query)) {
                $query = 'SELECT `title` FROM `or_statiordine` LEFT JOIN `or_statiordine_lang` ON (`or_statiordine_lang`.`id_record` = `or_statiordine`.`id` AND `or_statiordine_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `or_statiordine`.`id`='.prepare($idstatoordine);
                $rs = $dbo->fetchArray($query);

                // Ricalcolo inps, ritenuta e bollo (se l'ordine non è stato evaso)
                if ($dir == 'entrata') {
                    if ($rs[0]['name'] != 'Evaso') {
                        ricalcola_costiagg_ordine($id_record);
                    }
                } else {
                    if ($rs[0]['name'] != 'Evaso') {
                        ricalcola_costiagg_ordine($id_record, $idrivalsainps, $idritenutaacconto, $bollo);
                    }
                }

                flash()->info(tr('Ordine modificato correttamente!'));
            }
        }

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
        } catch (UnexpectedValueException) {
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
        $sconto->confermato = ($dir == 'entrata' ? setting('Conferma automaticamente le quantità negli ordini cliente') : setting('Conferma automaticamente le quantità negli ordini fornitore'));

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
        $id_righe = (array) post('righe');

        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Descrizione::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            try {
                $riga->delete();
            } catch (InvalidArgumentException) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
            }

            $riga = null;
        }

        ricalcola_costiagg_ordine($id_record);

        if (count($id_righe) == 1) {
            flash()->info(tr('Riga eliminata!'));
        } else {
            flash()->info(tr('Righe eliminate!'));
        }

        break;

        // Duplicazione riga
    case 'copy_riga':
        $id_righe = (array) post('righe');

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
        } catch (InvalidArgumentException) {
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
        if (!is_subclass_of($class, Common\Document::class)) {
            return;
        }
        $documento = $class::find($id_documento);

        // Individuazione sede
        $idsede_partenza = ($documento->direzione == 'entrata') ? $documento->idsede_partenza : $documento->idsede_destinazione;
        $idsede_partenza = $idsede_partenza ?: 0;
        $idsede_destinazione = ($documento->direzione == 'entrata') ? $documento->idsede_destinazione : $documento->idsede_partenza;
        $idsede_destinazione = $idsede_destinazione ?: 0;

        // Creazione dell' ordine al volo
        if (post('create_document') == 'on') {
            $tipo = Tipo::where('dir', $documento->direzione)->first();

            $ordine = Ordine::build($documento->anagrafica, $tipo, post('data'), post('id_segment'));
            $ordine->idpagamento = $documento->idpagamento;
            $ordine->idsede_partenza = $idsede_partenza;
            $ordine->idsede_destinazione = $idsede_destinazione;

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

                $dbo->insert('co_riferimenti_righe', [
                    'source_type' => $copia::class,
                    'source_id' => $copia->id,
                    'target_type' => $riga::class,
                    'target_id' => $riga->id,
                ]);
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
            $id_articolo = $dbo->selectOne('mg_articoli', 'id', ['deleted_at' => null, 'attivo' => 1, 'barcode' => $barcode])['id'];
            if (empty($id_articolo)) {
                $id_articolo = $dbo->selectOne('mg_articoli', 'id', ['deleted_at' => null, 'attivo' => 1, 'barcode' => '', 'codice' => $barcode])['id'];
            }
        }

        if (!empty($id_articolo)) {
            $permetti_movimenti_sotto_zero = setting('Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita');
            $qta_articolo = $dbo->selectOne('mg_articoli', 'qta', ['id' => $id_articolo])['qta'];

            $originale = ArticoloOriginale::find($id_articolo);

            $articolo = Articolo::build($ordine, $originale);
            $qta = 1;

            $articolo->um = $originale->um;
            $articolo->qta = 1;
            $articolo->costo_unitario = $originale->prezzo_acquisto;
            $articolo->confermato = ($dir == 'entrata' ? setting('Conferma automaticamente le quantità negli ordini cliente') : setting('Conferma automaticamente le quantità negli ordini fornitore'));

            if ($dir == 'entrata') {
                // L'aliquota dell'articolo ha precedenza solo se ha aliquota a 0, altrimenti anagrafica -> articolo -> impostazione
                if ($originale->idiva_vendita) {
                    $aliquota_articolo = floatval(Aliquota::find($originale->idiva_vendita)->percentuale);
                }
                $id_iva = ($ordine->anagrafica->idiva_vendite && (!$originale->idiva_vendita || $aliquota_articolo != 0) ? $ordine->anagrafica->idiva_vendite : $originale->idiva_vendita) ?: setting('Iva predefinita');
            } else {
                $id_iva = ($ordine->anagrafica->idiva_acquisti ?: ($originale->idiva_vendita ?: setting('Iva predefinita')));
            }
            $id_anagrafica = $ordine->idanagrafica;
            $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

            // CALCOLO PREZZO UNITARIO
            $prezzo_consigliato = getPrezzoConsigliato($id_anagrafica, $dir, $id_articolo);
            if (!$prezzo_consigliato['prezzo_unitario']) {
                $prezzo_consigliato = getPrezzoConsigliato(setting('Azienda predefinita'), $dir, $id_articolo);
            }
            $prezzo_unitario = $prezzo_consigliato['prezzo_unitario'];
            $sconto = $prezzo_consigliato['sconto'];

            if ($dir == 'entrata') {
                $prezzo_unitario = $prezzo_unitario ?: ($prezzi_ivati ? $originale->prezzo_vendita_ivato : $originale->prezzo_vendita);
            } else {
                $prezzo_unitario = $prezzo_unitario ?: $originale->prezzo_acquisto;
            }
            $provvigione = $dbo->selectOne('an_anagrafiche', 'provvigione_default', ['idanagrafica' => $ordine->idagente])['provvigione_default'];

            // Aggiunta sconto combinato se è presente un piano di sconto nell'anagrafica
            $join = ($dir == 'entrata' ? 'id_piano_sconto_vendite' : 'id_piano_sconto_acquisti');
            $piano_sconto = $dbo->fetchOne('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_piani_sconto ON an_anagrafiche.'.$join.'=mg_piani_sconto.id WHERE idanagrafica='.prepare($id_anagrafica));
            if (!empty($piano_sconto)) {
                $sconto = parseScontoCombinato($piano_sconto['prc_guadagno'].'+'.$sconto);
            }

            $articolo->setPrezzoUnitario($prezzo_unitario, $id_iva);
            $articolo->setSconto($sconto, 'PRC');
            $articolo->setProvvigione($provvigione ?: 0, 'PRC');
            $articolo->save();

            flash()->info(tr('Nuovo articolo aggiunto!'));
        } else {
            $response['error'] = tr('Nessun articolo corrispondente a magazzino');
            echo json_encode($response);
        }

        break;

    case 'update_inline':
        $id_riga = post('riga_id');
        $riga = $riga ?: Riga::find($id_riga);
        $riga = $riga ?: Articolo::find($id_riga);
        $riga = $riga ?: Sconto::find($id_riga);

        if (!empty($riga)) {
            if ($riga->isSconto()) {
                $riga->setScontoUnitario(post('sconto'), $riga->idiva);
            } else {
                $riga->qta = post('qta');
                $riga->setPrezzoUnitario(post('prezzo'), $riga->idiva);
                $riga->setSconto(post('sconto'), post('tipo_sconto'));
                $riga->costo_unitario = post('costo') ?: 0;
            }
            $riga->save();

            flash()->info(tr('Riga aggiornata!'));
        }

        break;

    case 'edit-price':
        $righe = (array) post('righe');
        $numero_totale = 0;

        foreach ($righe as $riga) {
            if ($riga['id'] != null) {
                $articolo = Articolo::find($riga['id']);
            }

            if ($articolo->prezzo_unitario != $riga['price']) {
                $articolo->setPrezzoUnitario($riga['price'], $articolo->idiva);
                $articolo->save();
                ++$numero_totale;
            }
        }

        if ($numero_totale > 1) {
            flash()->info(tr('_NUM_ prezzi modificati!', [
                '_NUM_' => $numero_totale,
            ]));
        } elseif ($numero_totale == 1) {
            flash()->info(tr('_NUM_ prezzo modificato!', [
                '_NUM_' => $numero_totale,
            ]));
        } else {
            flash()->warning(tr('Nessun prezzo modificato!'));
        }

        break;

    case 'update-price':
        $id_anagrafica = $ordine->idanagrafica;
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
        $numero_totale = 0;
        $id_righe = (array) post('righe');

        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);

            // CALCOLO PREZZO UNITARIO
            $prezzo_unitario = 0;
            $sconto = 0;
            if ($riga->isArticolo()) {
                $id_articolo = $riga->idarticolo;
                $prezzo_consigliato = getPrezzoConsigliato($id_anagrafica, $dir, $id_articolo);
                if (!$prezzo_consigliato['prezzo_unitario']) {
                    $prezzo_consigliato = getPrezzoConsigliato(setting('Azienda predefinita'), $dir, $id_articolo);
                }
                $prezzo_unitario = $prezzo_consigliato['prezzo_unitario'];
                $sconto = $prezzo_consigliato['sconto'];

                if ($dir == 'entrata') {
                    $prezzo_unitario = $prezzo_unitario ?: ($prezzi_ivati ? $riga->articolo->prezzo_vendita_ivato : $riga->articolo->prezzo_vendita);
                    $riga->costo_unitario = $riga->articolo->prezzo_acquisto;
                } else {
                    $prezzo_unitario = $prezzo_unitario ?: $riga->articolo->prezzo_acquisto;
                }
                $riga->setPrezzoUnitario($prezzo_unitario, $riga->idiva);
            }

            // Aggiunta sconto combinato se è presente un piano di sconto nell'anagrafica
            $join = ($dir == 'entrata' ? 'id_piano_sconto_vendite' : 'id_piano_sconto_acquisti');
            $piano_sconto = $dbo->fetchOne('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_piani_sconto ON an_anagrafiche.'.$join.'=mg_piani_sconto.id WHERE idanagrafica='.prepare($id_anagrafica));
            if (!empty($piano_sconto)) {
                $sconto = parseScontoCombinato($piano_sconto['prc_guadagno'].'+'.$sconto);
            }

            $riga->setSconto($sconto, 'PRC');
            $riga->save();
            ++$numero_totale;
        }

        if ($numero_totale > 1) {
            flash()->info(tr('_NUM_ prezzi modificati!', [
                '_NUM_' => $numero_totale,
            ]));
        } elseif ($numero_totale == 1) {
            flash()->info(tr('_NUM_ prezzo modificato!', [
                '_NUM_' => $numero_totale,
            ]));
        } else {
            flash()->warning(tr('Nessun prezzo modificato!'));
        }

        break;

        // Duplica ordine
    case 'copy':
        $new = $ordine->replicate();
        $new->numero = Ordine::getNextNumero(post('data'), $ordine->tipo->dir, $ordine->id_segment);
        $new->numero_esterno = Ordine::getNextNumeroSecondario(post('data'), $ordine->tipo->dir, $ordine->id_segment);
        $new->idstatoordine = post('idstatoordine');
        $new->data = post('data');
        $new->save();

        $id_record = $new->id;

        if (!empty(post('copia_righe'))) {
            $righe = $ordine->getRighe();
            foreach ($righe as $riga) {
                $new_riga = $riga->replicate();
                $new_riga->setDocument($new);

                $new_riga->qta_evasa = 0;
                $new_riga->save();
            }
        }

        // copia allegati
        if (!empty(post('copia_allegati'))) {
            $allegati = $ordine->uploads();
            foreach ($allegati as $allegato) {
                $allegato->copia([
                    'id_module' => $new->getModule()->id,
                    'id_record' => $new->id,
                ]);
            }
        }

        flash()->info(tr('Aggiunto ordine numero _NUM_!', [
            '_NUM_' => $new->numero,
        ]));

        break;
}
