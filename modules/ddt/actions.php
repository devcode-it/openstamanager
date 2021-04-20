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
use Modules\DDT\Components\Articolo;
use Modules\DDT\Components\Descrizione;
use Modules\DDT\Components\Riga;
use Modules\DDT\Components\Sconto;
use Modules\DDT\DDT;
use Modules\DDT\Tipo;

$module = Modules::get($id_module);

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $data = post('data');
        $id_tipo = post('idtipoddt');

        $anagrafica = Anagrafica::find($idanagrafica);
        $tipo = Tipo::find($id_tipo);

        $ddt = DDT::build($anagrafica, $tipo, $data);
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

            $tipo_sconto = post('tipo_sconto_generico');
            $sconto = post('sconto_generico');

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
        $articolo->um = post('um') ?: null;

        $articolo->costo_unitario = post('costo_unitario') ?: 0;
        $articolo->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $articolo->setSconto(post('sconto'), post('tipo_sconto'));

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
        $riga->um = post('um') ?: null;

        $riga->costo_unitario = post('costo_unitario') ?: 0;
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
        ricalcola_costiagg_ddt($id_record);

        break;

    case 'manage_descrizione':
        if (post('idriga') != null) {
            $riga = Descrizione::find(post('idriga'));
        } else {
            $riga = Descrizione::build($ddt);
        }

        $riga->descrizione = post('descrizione');

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

            $ddt = DDT::build($documento->anagrafica, $tipo, post('data'));
            $ddt->idpagamento = $documento->idpagamento;

            $ddt->id_documento_fe = $documento->id_documento_fe;
            $ddt->codice_cup = $documento->codice_cup;
            $ddt->codice_cig = $documento->codice_cig;
            $ddt->num_item = $documento->num_item;
            $ddt->idsede_destinazione = $id_sede;

            $ddt->idcausalet = post('id_causale_trasporto');
            $ddt->idreferente = $documento->idreferente;

            $ddt->save();

            $id_record = $ddt->id;
        }

        if (!empty($documento->sconto_finale)) {
            $ddt->sconto_finale = $documento->sconto_finale;
        } elseif (!empty($documento->sconto_finale_percentuale)) {
            $ddt->sconto_finale_percentuale = $documento->sconto_finale_percentuale;
        }

        $ddt->save();

        $righe = $documento->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on' and !empty(post('qta_da_evadere')[$riga->id])) {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($ddt, $qta);

                // Aggiornamento seriali dalla riga dell'ordine
                if ($copia->isArticolo()) {
                    if ($documento->tipo->dir == 'uscita') {
                        $originale = ArticoloOriginale::find($riga->idarticolo);
                        $id_iva = $originale->idiva_vendita ? $originale->idiva_vendita : setting('Iva predefinita');
                        $copia->setPrezzoUnitario($originale->prezzo_vendita, $id_iva);
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

    // Scollegamento riga generica da ddt
    case 'delete_riga':
        $id_riga = post('riga_id');
        $type = post('riga_type');

        $riga = $ddt->getRiga($type, $id_riga);

        if (!empty($riga)) {
            try {
                $riga->delete();

                flash()->info(tr('Riga rimossa!'));
            } catch (InvalidArgumentException $e) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
            }
        }

        ricalcola_costiagg_ddt($id_record);

        break;

    // eliminazione ddt
    case 'delete':
        try {
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
}

// Aggiornamento stato degli ordini presenti in questa fattura in base alle quantità totali evase
if (!empty($id_record) && setting('Cambia automaticamente stato ordini fatturati')) {
    $rs = $dbo->fetchArray('SELECT idordine FROM dt_righe_ddt WHERE idddt='.prepare($id_record));

    for ($i = 0; $i < sizeof($rs); ++$i) {
        $dbo->query('UPDATE or_ordini SET idstatoordine=(SELECT id FROM or_statiordine WHERE descrizione="'.get_stato_ordine($rs[$i]['idordine']).'") WHERE id = '.prepare($rs[$i]['idordine']));
    }
}
