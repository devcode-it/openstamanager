<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Anagrafiche\Anagrafica;
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

    case 'manage_articolo':
        if (post('idriga') != null) {
            $articolo = Articolo::find(post('idriga'));
        } else {
            $originale = ArticoloOriginale::find(post('idarticolo'));
            $articolo = Articolo::build($ordine, $originale);
        }

        $articolo->descrizione = post('descrizione');
        $articolo->um = post('um') ?: null;
        $articolo->id_iva = post('idiva');

        $articolo->prezzo_unitario_acquisto = post('prezzo_acquisto') ?: 0;
        $articolo->prezzo_unitario_vendita = post('prezzo');
        $articolo->sconto_unitario = post('sconto');
        $articolo->tipo_sconto = post('tipo_sconto');

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
        ricalcola_costiagg_ordine($id_record);

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($ordine);
        }

        $sconto->descrizione = post('descrizione');
        $sconto->id_iva = post('idiva');

        $sconto->sconto_unitario = post('sconto_unitario');
        $sconto->tipo_sconto = 'UNT';

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
        $riga->id_iva = post('idiva');

        $riga->prezzo_unitario_acquisto = post('prezzo_acquisto') ?: 0;
        $riga->prezzo_unitario_vendita = post('prezzo');
        $riga->sconto_unitario = post('sconto');
        $riga->tipo_sconto = post('tipo_sconto');

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

    // Scollegamento articolo da ordine
    case 'unlink_articolo':
        $idarticolo = post('idarticolo');
        $idriga = post('idriga');

        if (!empty($idarticolo)) {
            if (!rimuovi_articolo_daordine($idarticolo, $id_record, $idriga)) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));

                return;
            }

            // if( $dbo->query($query) ){
            // Ricalcolo inps, ritenuta e bollo
            if ($dir == 'entrata') {
                ricalcola_costiagg_ordine($id_record);
            } else {
                ricalcola_costiagg_ordine($id_record, 0, 0, 0);
            }

            flash()->info(tr('Articolo rimosso!'));
        }

        break;

    // Scollegamento riga generica da ordine
    case 'unlink_riga':
        $idriga = post('idriga');

        if (!empty($idriga)) {
            $query = 'DELETE FROM or_righe_ordini WHERE idordine='.prepare($id_record).' AND id='.prepare($idriga);

            $dbo->query($query);

            // Ricalcolo inps, ritenuta e bollo
            if ($dir == 'entrata') {
                ricalcola_costiagg_ordine($id_record);
            } else {
                ricalcola_costiagg_ordine($id_record, 0, 0, 0);
            }

            flash()->info(tr('Riga rimossa!'));
        }

        break;

    // eliminazione ordine
    case 'delete':
        // Se ci sono degli articoli collegati (ma non collegati a preventivi o interventi) li rimetto nel magazzino
        $query = 'SELECT id, idarticolo FROM or_righe_ordini WHERE idordine='.prepare($id_record).' AND NOT idarticolo=0';
        $rs = $dbo->fetchArray($query);

        foreach ($rs as $value) {
            $non_rimovibili = seriali_non_rimuovibili('id_riga_documento', $value['id'], $dir);
            if (!empty($non_rimovibili)) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));

                return;
            }
        }

        $dbo->query('DELETE FROM or_ordini WHERE id='.prepare($id_record));
        $dbo->query('DELETE FROM or_righe_ordini WHERE idordine='.prepare($id_record));
        flash()->info(tr('Ordine eliminato!'));

        break;

    case 'add_serial':
        $idriga = post('idriga');
        $idarticolo = post('idarticolo');

        $serials = (array) post('serial');
        foreach ($serials as $key => $value) {
            if (empty($value)) {
                unset($serials[$key]);
            }
        }

        $dbo->sync('mg_prodotti', ['id_riga_ordine' => $idriga, 'dir' => $dir, 'id_articolo' => $idarticolo], ['serial' => $serials]);

        break;

        case 'update_position':
            $orders = explode(',', $_POST['order']);
            $order = 0;

            foreach ($orders as $idriga) {
                $dbo->query('UPDATE `or_righe_ordini` SET `order`='.prepare($order).' WHERE id='.prepare($idriga));
                ++$order;
            }

            break;

    // Aggiunta di un preventivo in ordine
    case 'add_preventivo':
        $preventivo = \Modules\Preventivi\Preventivo::find(post('id_documento'));

        // Creazione della fattura al volo
        if (post('create_document') == 'on') {
            $tipo = Tipo::where('dir', $dir)->first();

            $ordine = Ordine::build($preventivo->anagrafica, $tipo, post('data'));
            $ordine->idpagamento = $preventivo->idpagamento;

            $ordine->id_documento_fe = $preventivo->id_documento_fe;
            $ordine->codice_cup = $preventivo->codice_cup;
            $ordine->codice_cig = $preventivo->codice_cig;
            $ordine->num_item = $preventivo->num_item;

            $ordine->save();

            $id_record = $ordine->id;
        }

        $parziale = false;
        $righe = $preventivo->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on') {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($ordine, $qta);

                // Aggiornamento seriali dalla riga dell'ordine
                if ($copia->isArticolo()) {
                    $copia->movimenta($copia->qta);
                }

                $copia->save();
            }

            if ($riga->qta != $riga->qta_evasa) {
                $parziale = true;
            }
        }

        ricalcola_costiagg_ordine($id_record);

        flash()->info(tr('Preventivo _NUM_ aggiunto!', [
            '_NUM_' => $preventivo->numero,
        ]));

        break;
}
