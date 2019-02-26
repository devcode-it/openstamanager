<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\DDT\Components\Articolo;
use Modules\DDT\Components\Descrizione;
use Modules\DDT\Components\Riga;
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

        flash()->info(tr('Aggiunto ddt in _TYPE_ numero _NUM_!', [
            '_TYPE_' => $dir,
            '_NUM_' => $ddt->numero,
        ]));

        break;

    case 'update':
        if (!empty($id_record)) {
            $idstatoddt = post('idstatoddt');
            $idpagamento = post('idpagamento');

            $totale_imponibile = get_imponibile_ddt($id_record);
            $totale_ddt = get_totale_ddt($id_record);

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

            // Query di aggiornamento
            $dbo->update('dt_ddt', [
                'data' => post('data'),
                'numero_esterno' => post('numero_esterno'),
                'note' => post('note'),
                'note_aggiuntive' => post('note_aggiuntive'),

                'idstatoddt' => $idstatoddt,
                'idpagamento' => $idpagamento,
                'idconto' => post('idconto'),
                'idanagrafica' => post('idanagrafica'),
                'idspedizione' => post('idspedizione'),
                'idcausalet' => post('idcausalet'),
                'idsede' => post('idsede'),
                'idvettore' => post('idvettore'),
                'idporto' => post('idporto'),
                'idaspettobeni' => post('idaspettobeni'),
                'idrivalsainps' => $idrivalsainps,
                'idritenutaacconto' => $idritenutaacconto,

                'n_colli' => post('n_colli'),
                'bollo' => 0,
                'rivalsainps' => 0,
                'ritenutaacconto' => 0,
            ], ['id' => $id_record]);

            // Aggiornamento sconto
            $dbo->update('dt_ddt', [
                'tipo_sconto_globale' => post('tipo_sconto_generico'),
                'sconto_globale' => post('sconto_generico'),
            ], ['id' => $id_record]);

            aggiorna_sconto([
                'parent' => 'dt_ddt',
                'row' => 'dt_righe_ddt',
            ], [
                'parent' => 'id',
                'row' => 'idddt',
            ], $id_record);

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

            flash()->info(tr('Ddt modificato correttamente!'));
        }
        break;

    case 'addarticolo':
        if (post('idarticolo') !== null) {
            $dir = post('dir');

            $idarticolo = post('idarticolo');
            $descrizione = post('descrizione');
            $idiva = post('idiva');

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

            add_articolo_inddt($id_record, $idarticolo, $descrizione, $idiva, $qta, post('um'), $prezzo * $qta, $sconto, $sconto_unitario, $tipo_sconto);

            // Ricalcolo inps, ritenuta e bollo
            ricalcola_costiagg_ddt($id_record);

            flash()->info(tr('Articolo aggiunto!'));
        }
        break;

    case 'addriga':
        // Selezione costi da intervento
        $descrizione = post('descrizione');
        $idiva = post('idiva');
        $um = post('um');

        $prezzo = post('prezzo');
        $qta = post('qta');

        // Calcolo dello sconto
        $sconto_unitario = post('sconto');
        $tipo_sconto = post('tipo_sconto');
        $sconto = calcola_sconto([
            'sconto' => $sconto_unitario,
            'prezzo' => $prezzo,
            'tipo' => $tipo_sconto,
            'qta' => $qta,
        ]);

        $subtot = $prezzo * $qta;

        // Calcolo iva
        $query = 'SELECT descrizione, percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva);
        $rs = $dbo->fetchArray($query);
        $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];

        $query = 'INSERT INTO dt_righe_ddt(idddt, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, is_descrizione, `order`) VALUES('.prepare($id_record).', '.prepare($idiva).', '.prepare($rs[0]['descrizione']).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', '.prepare(empty($qta)).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM dt_righe_ddt AS t WHERE idddt='.prepare($id_record).'))';
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
            ricalcola_costiagg_ddt($id_record);
        } else {
            ricalcola_costiagg_ddt($id_record);
        }

        break;

		
    // Aggiunta di un ordine in ddt
    case 'add_ordine':
	
        $ordine = \Modules\Ordini\Ordine::find(post('id_ordine'));

        // Creazione della fattura al volo
        if (post('create_document') == 'on') {
            $tipo = Tipo::where('dir', $dir)->first();

            $ddt = DDT::build($ordine->anagrafica, $tipo, post('data'));
            $ddt->idpagamento = $ordine->idpagamento;
            $ddt->save();

            $id_record = $ddt->id;
        }

        $parziale = false;

        //$id_iva = post('id_iva');
        //$id_conto = post('id_conto');
        $righe = $ordine->getRighe();

        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on') {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($ddt, $qta);
        		
                $copia->save();

                // Aggiornamento seriali dalla riga dell'ordine
                if ($copia->isArticolo()) {
                    $copia->movimenta($copia->qta);

                    $serials = is_array(post('serial')[$riga->id]) ? post('serial')[$riga->id] : [];

                    $copia->serials = $serials;
                }
            }

            if ($riga->qta != $riga->qta_evasa) {
                $parziale = true;
            }
        }

        // Impostazione del nuovo stato
        $descrizione = $parziale ? 'Parzialmente evaso' : 'Evaso';
        $stato = \Modules\Ordini\Stato::where('descrizione', $descrizione)->first();
        $ordine->stato()->associate($stato);
        $ordine->save();

        ricalcola_costiagg_ddt($id_record);

        flash()->info(tr('Ordine _NUM_ aggiunto!', [
            '_NUM_' => $ordine->numero,
        ]));

        break;
	
	
    // Scollegamento articolo da ddt
    case 'unlink_articolo':
        $idriga = post('idriga');
        $idarticolo = post('idarticolo');

        if (!rimuovi_articolo_daddt($idarticolo, $id_record, $idriga)) {
            flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));

            return;
        }

        // Ricalcolo inps, ritenuta e bollo
        if ($dir == 'entrata') {
            ricalcola_costiagg_ddt($id_record);
        } else {
            ricalcola_costiagg_ddt($id_record, 0, 0, 0);
        }

        flash()->info(tr('Articolo rimosso!'));
        break;

    // Scollegamento riga generica da ddt
    case 'unlink_riga':
        $idriga = post('idriga');

        if ($id_record != '' && $idriga != '') {
            // Se la riga è stata creata da un ordine, devo riportare la quantità evasa nella tabella degli ordini
            // al valore di prima, riaggiungendo la quantità che sto togliendo
            $rs = $dbo->fetchArray('SELECT qta, descrizione, idarticolo, idordine, idiva FROM dt_righe_ddt WHERE idddt='.prepare($id_record).' AND id='.prepare($idriga));

            // Rimpiazzo la quantità negli ordini
            $dbo->query('UPDATE or_righe_ordini SET qta_evasa=qta_evasa-'.$rs[0]['qta'].' WHERE descrizione='.prepare($rs[0]['descrizione']).' AND idarticolo='.prepare($rs[0]['idarticolo']).' AND idordine='.prepare($rs[0]['idordine']).' AND idiva='.prepare($rs[0]['idiva']));

            // Eliminazione delle righe dal ddt
            $query = 'DELETE FROM dt_righe_ddt WHERE idddt='.prepare($id_record).' AND id='.prepare($idriga);

            if ($dbo->query($query)) {
                //Aggiorno lo stato dell'ordine
                if (setting('Cambia automaticamente stato ordini fatturati') && !empty($rs[0]['idordine'])) {
                    $dbo->query('UPDATE or_ordini SET idstatoordine=(SELECT id FROM or_statiordine WHERE descrizione="'.get_stato_ordine($rs[0]['idordine']).'") WHERE id = '.prepare($rs[0]['idordine']));
                }

                // Ricalcolo inps, ritenuta e bollo
                if ($dir == 'entrata') {
                    ricalcola_costiagg_ddt($id_record);
                } else {
                    ricalcola_costiagg_ddt($id_record, 0, 0, 0);
                }

                flash()->info(tr('Riga rimossa!'));
            }
        }
        break;

    // Modifica riga
    case 'editriga':
        if (post('idriga') !== null) {
            // Selezione costi da intervento
            $idriga = post('idriga');
            $descrizione = post('descrizione');

            $prezzo = post('prezzo');
            $qta = post('qta');

            // Calcolo dello sconto
            $sconto_unitario = post('sconto');
            $tipo_sconto = post('tipo_sconto');
            $sconto = calcola_sconto([
                'sconto' => $sconto_unitario,
                'prezzo' => $prezzo,
                'tipo' => $tipo_sconto,
                'qta' => $qta,
            ]);

            $idiva = post('idiva');
            $um = post('um');

            $subtot = $prezzo * $qta;

            // Lettura idarticolo dalla riga ddt
            $rs = $dbo->fetchArray('SELECT * FROM dt_righe_ddt WHERE id='.prepare($idriga));
            $idarticolo = $rs[0]['idarticolo'];
            $idordine = $rs[0]['idordine'];
            $old_qta = $rs[0]['qta'];
            $is_descrizione = $rs[0]['is_descrizione'];

            // Controllo per gestire i serial
            if (!empty($idarticolo)) {
                if (!controlla_seriali('id_riga_ddt', $idriga, $old_qta, $qta, $dir)) {
                    flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));

                    return;
                }
            }

            // Se c'è un collegamento ad un ordine, aggiorno la quantità evasa
            if (!empty($idordine)) {
                $dbo->query('UPDATE or_righe_ordini SET qta_evasa=qta_evasa-'.$old_qta.' + '.$qta.' WHERE descrizione='.prepare($rs[0]['descrizione']).' AND idarticolo='.prepare($rs[0]['idarticolo']).' AND idordine='.prepare($idordine).' AND idiva='.prepare($rs[0]['idiva']));
            }

            // Calcolo iva
            $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
            $rs = $dbo->fetchArray($query);
            $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
            $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
            $desc_iva = $rs[0]['descrizione'];

            // Modifica riga generica sul ddt
            if ($is_descrizione == 0) {
                $query = 'UPDATE dt_righe_ddt SET idiva='.prepare($idiva).', desc_iva='.prepare($desc_iva).', iva='.prepare($iva).', iva_indetraibile='.prepare($iva_indetraibile).', descrizione='.prepare($descrizione).', subtotale='.prepare($subtot).', sconto='.prepare($sconto).', sconto_unitario='.prepare($sconto_unitario).', tipo_sconto='.prepare($tipo_sconto).', um='.prepare($um).', qta='.prepare($qta).' WHERE id='.prepare($idriga);
            } else {
                $query = 'UPDATE dt_righe_ddt SET descrizione='.prepare($descrizione).' WHERE id='.prepare($idriga);
            }
            if ($dbo->query($query)) {
                if (!empty($idarticolo)) {
                    // Controlli aggiuntivi sulle quantità evase degli ordini
                    if (!empty($idordine) && $qta > 0) {
                        $rs = $dbo->fetchArray('SELECT qta_evasa, qta FROM or_righe_ordini WHERE idordine='.prepare($idordine).' AND idarticolo='.prepare($idarticolo));

                        $qta_ordine = $qta;
                        if ($qta > $rs[0]['qta_evasa']) {
                            $qta_ordine = ($qta > $rs[0]['qta']) ? $rs[0]['qta'] : $qta;
                        }

                        $dbo->query('UPDATE or_righe_ordini SET qta_evasa = '.prepare($qta_ordine).' WHERE idordine='.prepare($idordine).' AND idarticolo='.prepare($idarticolo));
                    }

                    $new_qta = $qta - $old_qta;
                    $new_qta = ($dir == 'entrata') ? -$new_qta : $new_qta;
                    add_movimento_magazzino($idarticolo, $new_qta, ['idddt' => $id_record]);
                }

                flash()->info(tr('Riga modificata!'));

                // Ricalcolo inps, ritenuta e bollo
                if ($dir == 'entrata') {
                    ricalcola_costiagg_ddt($id_record);
                } else {
                    ricalcola_costiagg_ddt($id_record);
                }
            }
        }
        break;

    // eliminazione ddt
    case 'delete':
        // Se ci sono degli articoli collegati
        $rs = $dbo->fetchArray('SELECT id, idarticolo FROM dt_righe_ddt WHERE idddt='.prepare($id_record));

        foreach ($rs as $value) {
            $non_rimovibili = seriali_non_rimuovibili('id_riga_ddt', $value['id'], $dir);
            if (!empty($non_rimovibili)) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));

                return;
            }
        }

        for ($i = 0; $i < sizeof($rs); ++$i) {
            if ($rs[$i]['idarticolo']) {
                rimuovi_articolo_daddt($rs[$i]['idarticolo'], $id_record, $rs[$i]['id']);
            }
        }

        // Se delle righe sono state create da un ordine, devo riportare la quantità evasa nella tabella degli ordini
        // al valore di prima, riaggiungendo la quantità che sto togliendo
        $rs = $dbo->fetchArray('SELECT qta, descrizione, idarticolo, idordine, idiva FROM dt_righe_ddt WHERE idddt='.prepare($id_record).' AND idarticolo="0"');

        // Rimpiazzo la quantità negli ordini
        for ($i = 0; $i < sizeof($rs); ++$i) {
            $dbo->query('UPDATE or_righe_ordini SET qta_evasa=qta_evasa-'.$rs[$i]['qta'].' WHERE descrizione='.prepare($rs[$i]['descrizione']).' AND idarticolo='.prepare($rs[$i]['idarticolo']).' AND idordine='.prepare($rs[$i]['idordine']).' AND idiva='.prepare($rs[$i]['idiva']));
        }

        $dbo->query('DELETE FROM dt_ddt WHERE id='.prepare($id_record));
        $dbo->query('DELETE FROM dt_righe_ddt WHERE idddt='.prepare($id_record));
        $dbo->query('DELETE FROM mg_movimenti WHERE idddt='.prepare($id_record));

        //Aggiorno gli stati degli ordini
        if (setting('Cambia automaticamente stato ordini fatturati')) {
            for ($i = 0; $i < sizeof($rs); ++$i) {
                $dbo->query('UPDATE or_ordini SET idstatoordine=(SELECT id FROM or_statiordine WHERE descrizione="'.get_stato_ordine($rs[$i]['idordine']).'") WHERE id = '.prepare($rs[$i]['idordine']));
            }
        }

        flash()->info(tr('Ddt eliminato!'));

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

        $dbo->sync('mg_prodotti', ['id_riga_ddt' => $idriga, 'dir' => $dir, 'id_articolo' => $idarticolo], ['serial' => $serials]);

        break;

        case 'update_position':
            $orders = explode(',', $_POST['order']);
            $order = 0;

            foreach ($orders as $idriga) {
                $dbo->query('UPDATE `dt_righe_ddt` SET `order`='.prepare($order).' WHERE id='.prepare($idriga));
                ++$order;
            }

            break;

    // aggiungi righe da ordine
    /*case 'add_ordine':
        $idordine = post('iddocumento');

        // Lettura di tutte le righe della tabella in arrivo
        foreach (post('qta_da_evadere') as $i => $value) {
            // Processo solo le righe da evadere
            if (post('evadere')[$i] == 'on') {
                $idrigaordine = $i;
                $idarticolo = post('idarticolo')[$i];
                $descrizione = post('descrizione')[$i];

                $qta = post('qta_da_evadere')[$i];
                $um = post('um')[$i];

                $subtot = post('subtot')[$i] * $qta;
                $sconto = post('sconto')[$i];
                $sconto = $sconto * $qta;

                $qprc = 'SELECT tipo_sconto, sconto_unitario FROM or_righe_ordini WHERE id='.prepare($idrigaordine);
                $rsprc = $dbo->fetchArray($qprc);

                $sconto_unitario = $rsprc[0]['sconto_unitario'];
                $tipo_sconto = $rsprc[0]['tipo_sconto'];

                $idiva = post('idiva')[$i];

                // Calcolo l'iva indetraibile
                $q = 'SELECT percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva);
                $rs = $dbo->fetchArray($q);
                $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
                $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];

                // Leggo la descrizione iva
                $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
                $rs = $dbo->fetchArray($query);
                $desc_iva = $rs[0]['descrizione'];

                // Se sto aggiungendo un articolo uso la funzione per inserirlo e incrementare la giacenza
                if (!empty($idarticolo)) {
                    $idiva_acquisto = $idiva;
                    $prezzo_acquisto = $subtot;
                    $riga = add_articolo_inddt($id_record, $idarticolo, $descrizione, $idiva, $qta, $um, $prezzo_acquisto, $sconto, $sconto_unitario, $tipo_sconto);

                    // Lettura lotto, serial, altro dalla riga dell'ordine
                    $dbo->query('INSERT INTO mg_prodotti (id_riga_documento, id_articolo, dir, serial, lotto, altro) SELECT '.prepare($riga).', '.prepare($idarticolo).', '.prepare($dir).', serial, lotto, altro FROM mg_prodotti AS t WHERE id_riga_ordine='.prepare($idrigaordine));
                }

                // Inserimento riga normale
                elseif ($qta != 0) {
                    $query = 'INSERT INTO dt_righe_ddt(idddt, idarticolo, descrizione, idordine, idiva, desc_iva, iva, iva_indetraibile, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, `order`) VALUES('.prepare($id_record).', '.prepare($idarticolo).', '.prepare($descrizione).', '.prepare($idordine).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';
                    $dbo->query($query);
                }

                // Scalo la quantità dall ordine
                $dbo->query('UPDATE or_righe_ordini SET qta_evasa = qta_evasa+'.$qta.' WHERE id='.prepare($idrigaordine));
            }
        }

        ricalcola_costiagg_ddt($id_record);

        flash()->info(tr('Aggiunti nuovi articoli in ddt!'));

        break;
		*/
}

// Aggiornamento stato degli ordini presenti in questa fattura in base alle quantità totali evase
if (!empty($id_record) && setting('Cambia automaticamente stato ordini fatturati')) {
    $rs = $dbo->fetchArray('SELECT idordine FROM dt_righe_ddt WHERE idddt='.prepare($id_record));

    for ($i = 0; $i < sizeof($rs); ++$i) {
        $dbo->query('UPDATE or_ordini SET idstatoordine=(SELECT id FROM or_statiordine WHERE descrizione="'.get_stato_ordine($rs[$i]['idordine']).'") WHERE id = '.prepare($rs[$i]['idordine']));
    }
}

// Aggiornamento sconto sulle righe
if (post('op') !== null && post('op') != 'update') {
    aggiorna_sconto([
        'parent' => 'dt_ddt',
        'row' => 'dt_righe_ddt',
    ], [
        'parent' => 'id',
        'row' => 'idddt',
    ], $id_record);
}
