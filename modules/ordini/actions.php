<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Ordini\Components\Articolo;
use Modules\Ordini\Components\Descrizione;
use Modules\Ordini\Components\Riga;
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

            'sconto_globale' => $sconto,
            'tipo_sconto_globale' => $tipo_sconto,

            'bollo' => 0,
            'rivalsainps' => 0,
            'ritenutaacconto' => 0,

            'id_documento_fe' => post('id_documento_fe'),
            'codice_cup' => post('codice_cup'),
            'codice_cig' => post('codice_cig'),
        ], ['id' => $id_record]);

        if ($dbo->query($query)) {
            aggiorna_sconto([
                'parent' => 'or_ordini',
                'row' => 'or_righe_ordini',
            ], [
                'parent' => 'id',
                'row' => 'idordine',
            ], $id_record);

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

    case 'addarticolo':
        if (post('idarticolo') !== null) {
            $idarticolo = post('idarticolo');
            $idiva = post('idiva');
            $descrizione = post('descrizione');
            $qta = post('qta');
            $prezzo = post('prezzo');

            // Calcolo dello sconto
            $sconto_unitario = post('sconto');
            $tipo_sconto = post('tipo_sconto');
            $sconto = calcola_sconto([
                'sconto' => $sconto_unitario,
                'prezzo' => $prezzo,
                'tipo' => $tipo_sconto,
                'qta' => $qta,
            ]);

            add_articolo_inordine($id_record, $idarticolo, $descrizione, $idiva, $qta, post('um'), $prezzo * $qta, $sconto, $sconto_unitario, $tipo_sconto);

            flash()->info(tr('Articolo aggiunto!'));
        }
        ricalcola_costiagg_ordine($id_record);
        break;

    case 'addriga':
        // Selezione costi da intervento
        $descrizione = post('descrizione');
        $prezzo = post('prezzo');
        $qta = post('qta');
        $idiva = post('idiva');
        $um = post('um');
        $subtot = $prezzo * $qta;

        // Calcolo dello sconto
        $sconto_unitario = post('sconto');
        $tipo_sconto = post('tipo_sconto');
        $sconto = calcola_sconto([
            'sconto' => $sconto_unitario,
            'prezzo' => $prezzo,
            'tipo' => $tipo_sconto,
            'qta' => $qta,
        ]);

        // Calcolo iva
        $query = 'SELECT descrizione, percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva);
        $rs = $dbo->fetchArray($query);
        $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];

        $query = 'INSERT INTO or_righe_ordini(idordine, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, is_descrizione, `order`) VALUES('.prepare($id_record).', '.prepare($idiva).', '.prepare($rs[0]['descrizione']).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', '.prepare(empty($qta)).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM or_righe_ordini AS t WHERE idordine='.prepare($id_record).'))';
        $dbo->query($query);

        // Messaggi informativi
        if (!empty($idarticolo)) {
            flash()->info(tr('Articolo aggiunto!'));
        } elseif (!empty($qta)) {
            flash()->info(tr('Riga aggiunta!'));
        } else {
            flash()->info(tr('Riga descrittiva aggiunta!'));
        }

        // Ricalcolo inps, ritenuta e bollo
        if ($dir == 'entrata') {
            ricalcola_costiagg_ordine($id_record);
        } else {
            ricalcola_costiagg_ordine($id_record);
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

    // Modifica riga
    case 'editriga':
        if (post('idriga') !== null) {
            $idriga = post('idriga');
            $descrizione = post('descrizione');
            $prezzo = post('prezzo');
            $qta = post('qta');
            $idiva = post('idiva');
            $um = post('um');
            $subtot = $prezzo * $qta;

            // Calcolo dello sconto
            $sconto_unitario = post('sconto');
            $tipo_sconto = post('tipo_sconto');
            $sconto = calcola_sconto([
                'sconto' => $sconto_unitario,
                'prezzo' => $prezzo,
                'tipo' => $tipo_sconto,
                'qta' => $qta,
            ]);

            // Lettura idarticolo dalla riga documento
            $rs = $dbo->fetchArray('SELECT idordine, idarticolo, qta, abilita_serial, is_descrizione FROM or_righe_ordini WHERE id='.prepare($idriga));
            $idarticolo = $rs[0]['idarticolo'];
            $old_qta = $rs[0]['qta'];
            $idordine = $rs[0]['idordine'];
            $abilita_serial = $rs[0]['abilita_serial'];
            $is_descrizione = $rs[0]['is_descrizione'];

            // Controllo per gestire i serial
            if (!empty($idarticolo)) {
                if (!controlla_seriali('id_riga_ordine', $idriga, $old_qta, $qta, $dir)) {
                    flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));

                    return;
                }
            }

            // Calcolo iva
            $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
            $rs = $dbo->fetchArray($query);
            $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
            $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
            $desc_iva = $rs[0]['descrizione'];

            if ($is_descrizione == 0) {
                // Modifica riga generica sul documento
                $query = 'UPDATE or_righe_ordini SET idiva='.prepare($idiva).', desc_iva='.prepare($rs[0]['descrizione']).', iva='.prepare($iva).', iva_indetraibile='.prepare($iva_indetraibile).', descrizione='.prepare($descrizione).', subtotale='.prepare($subtot).', sconto='.prepare($sconto).', sconto_unitario='.prepare($sconto_unitario).', tipo_sconto='.prepare($tipo_sconto).', um='.prepare($um).', qta='.prepare($qta).' WHERE id='.prepare($idriga);
            } else {
                $query = 'UPDATE or_righe_ordini SET descrizione='.prepare($descrizione).' WHERE id='.prepare($idriga);
            }
            if ($dbo->query($query)) {
                flash()->info(tr('Riga modificata!'));

                // Ricalcolo inps, ritenuta e bollo
                if ($dir == 'entrata') {
                    ricalcola_costiagg_ordine($id_record);
                } else {
                    ricalcola_costiagg_ordine($id_record);
                }
            }
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
        $preventivo = \Modules\Preventivi\Preventivo::find(post('id_preventivo'));

        // Creazione della fattura al volo
        if (post('create_document') == 'on') {
            $tipo = Tipo::where('dir', $dir)->first();

            $ordine = Ordine::build($preventivo->anagrafica, $tipo, post('data'));
            $ordine->idpagamento = $preventivo->idpagamento;
            $ordine->save();

            $id_record = $ordine->id;
        }

        $parziale = false;

        $id_iva = post('id_iva');
        $id_conto = post('id_conto');
        $righe = $preventivo->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on') {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($ordine, $qta);
                $copia->idiva = $id_iva;
                $copia->id_conto = $id_conto;

                $copia->save();

                // Aggiornamento seriali dalla riga dell'ordine
                if ($copia->isArticolo()) {
                    $copia->movimenta($copia->qta);
                }
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

if (post('op') !== null && post('op') != 'update') {
    aggiorna_sconto([
        'parent' => 'or_ordini',
        'row' => 'or_righe_ordini',
    ], [
        'parent' => 'id',
        'row' => 'idordine',
    ], $id_record);
}
