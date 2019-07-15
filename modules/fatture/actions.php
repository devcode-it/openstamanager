<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Fatture\Components\Articolo;
use Modules\Fatture\Components\Descrizione;
use Modules\Fatture\Components\Riga;
use Modules\Fatture\Components\Sconto;
use Modules\Fatture\Fattura;
use Modules\Fatture\Tipo;

$module = Modules::get($id_module);

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $data = post('data');
        $idtipodocumento = post('idtipodocumento');
        $id_segment = post('id_segment');

        $anagrafica = Anagrafica::find($idanagrafica);
        $tipo = Tipo::find($idtipodocumento);

        $fattura = Fattura::build($anagrafica, $tipo, $data, $id_segment);
        $id_record = $fattura->id;

        aggiorna_sedi_movimenti('documenti', $id_record);
        flash()->info(tr('Aggiunta fattura numero _NUM_!', [
            '_NUM_' => $fattura->numero,
        ]));

        break;

    case 'update':
        if (post('id_record') !== null) {
            $fattura->data = post('data');
            $fattura->data_registrazione = post('data_registrazione');
            $fattura->data_competenza = post('data_competenza');

            $fattura->numero_esterno = post('numero_esterno');
            $fattura->note = post('note');
            $fattura->note_aggiuntive = post('note_aggiuntive');

            $fattura->idstatodocumento = post('idstatodocumento');
            $fattura->idtipodocumento = post('idtipodocumento');
            $fattura->idanagrafica = post('idanagrafica');
            $fattura->idagente = post('idagente');
            $fattura->idpagamento = post('idpagamento');
            $fattura->idbanca = post('idbanca');
            $fattura->idcausalet = post('idcausalet');
            $fattura->idspedizione = post('idspedizione');
            $fattura->idporto = post('idporto');
            $fattura->idaspettobeni = post('idaspettobeni');
            $fattura->idvettore = post('idvettore');
            $fattura->idsede_partenza = post('idsede_partenza');
            $fattura->idsede_destinazione = post('idsede_destinazione');
            $fattura->idconto = post('idconto');
            $fattura->split_payment = post('split_payment') ?: 0;
            $fattura->is_fattura_conto_terzi = post('is_fattura_conto_terzi') ?: 0;
            $fattura->n_colli = post('n_colli');
            $fattura->tipo_resa = post('tipo_resa');

            $fattura->rivalsainps = 0;
            $fattura->ritenutaacconto = 0;
            $fattura->iva_rivalsainps = 0;

            $fattura->codice_stato_fe = post('codice_stato_fe') ?: null;
            $fattura->id_ritenuta_contributi = post('id_ritenuta_contributi') ?: null;

            if ($dir == 'uscita') {
                $fattura->numero = post('numero');
                $fattura->numero_esterno = post('numero_esterno');
                $fattura->idrivalsainps = post('id_rivalsa_inps');
                $fattura->idritenutaacconto = post('id_ritenuta_acconto');
            }

            $fattura->addebita_bollo = post('addebita_bollo');
            $bollo_automatico = post('bollo_automatico');
            if (empty($bollo_automatico)) {
                $fattura->bollo = post('bollo');
            } else {
                $fattura->bollo = null;
            }

            $fattura->save();

            // Ricalcolo inps, ritenuta e bollo (se la fattura non è stata pagata)
            ricalcola_costiagg_fattura($id_record);

            $stato = $dbo->select('co_statidocumento', 'descrizione', ['id' => post('idstatodocumento')])[0];

            // Elimino la scadenza e tutti i movimenti, poi se la fattura è emessa le ricalcolo
            if ($stato['descrizione'] == 'Bozza' or $stato['descrizione'] == 'Annullata') {
                elimina_scadenza($id_record);
                //elimina_movimento($id_record, 0);
                //elimino movimento anche prima nota (se pagata o parzialmente pagata)
                elimina_movimento($id_record, 1);
            } elseif ($stato['descrizione'] == 'Emessa') {
                elimina_scadenza($id_record);
                elimina_movimento($id_record, 0);
            } elseif (($stato['descrizione'] == 'Pagato' or $stato['descrizione'] == 'Parzialmente pagato') and ($dbo->fetchNum('SELECT id  FROM co_scadenziario WHERE iddocumento = '.prepare($id_record)) == 0)) {
                // aggiungo la scadenza come già pagata
                aggiungi_scadenza($id_record, null, 1);
                aggiungi_movimento($id_record, $dir);
            }

            // Se la fattura è in stato "Emessa" posso inserirla in scadenzario e aprire il mastrino cliente
            if ($stato['descrizione'] == 'Emessa') {
                aggiungi_scadenza($id_record);
                aggiungi_movimento($id_record, $dir);
            }

            aggiorna_sedi_movimenti('documenti', $id_record);

            flash()->info(tr('Fattura modificata correttamente!'));
        }

        break;

    // Ricalcolo scadenze
    case 'ricalcola_scadenze':
        $fattura->registraScadenze(false, true);

        break;

    // Ricalcolo scadenze
    case 'controlla_totali':
        try {
            $xml = \Util\XML::read($fattura->getXML());

            $dati_generali = $xml['FatturaElettronicaBody']['DatiGenerali']['DatiGeneraliDocumento'];
            $totale_documento = $dati_generali['ImportoTotaleDocumento'] ?: null;
        } catch (Exception $e) {
            $totale_documento = null;
        }

        echo json_encode([
            'stored' => $totale_documento,
            'calculated' => $fattura->totale,
        ]);

        break;

    // eliminazione documento
    case 'delete':
        $rs = $dbo->fetchArray('SELECT id FROM co_righe_documenti WHERE iddocumento='.prepare($id_record));

        // Controllo sui seriali
        foreach ($rs as $r) {
            $non_rimovibili = seriali_non_rimuovibili('id_riga_documento', $r['id'], $dir);
            if (!empty($non_rimovibili)) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));

                return;
            }
        }

        // Rimozione righe
        foreach ($rs as $r) {
            rimuovi_riga_fattura($id_record, $r['id'], $dir);
        }

        // Se ci sono dei preventivi collegati li rimetto nello stato "In attesa di pagamento"
        $rs = $dbo->fetchArray('SELECT idpreventivo FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idpreventivo IS NOT NULL');
        for ($i = 0; $i < sizeof($rs); ++$i) {
            $dbo->query("UPDATE co_preventivi SET idstato=(SELECT id FROM co_statipreventivi WHERE descrizione='In lavorazione') WHERE id=".prepare($rs[$i]['idpreventivo']));
            $dbo->query('UPDATE co_righe_preventivi SET qta_evasa=0 WHERE idpreventivo='.prepare($rs[$i]['idpreventivo']));
        }

        // Se ci sono degli interventi collegati li rimetto nello stato "Completato"
        $rs = $dbo->fetchArray('SELECT idintervento FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idintervento IS NOT NULL');
        for ($i = 0; $i < sizeof($rs); ++$i) {
            $dbo->query("UPDATE in_interventi SET idstatointervento = (SELECT idstatointervento FROM in_statiintervento WHERE descrizione = 'Completato') WHERE id=".prepare($rs[$i]['idintervento']));
        }

        elimina_scadenza($id_record);
        elimina_movimento($id_record);

        $dbo->query('DELETE FROM co_documenti WHERE id='.prepare($id_record));
        $dbo->query('DELETE FROM co_scadenziario WHERE iddocumento='.prepare($id_record));
        $dbo->query('DELETE FROM co_movimenti WHERE iddocumento='.prepare($id_record));

        // Azzeramento collegamento della rata contrattuale alla pianificazione
        $dbo->query('UPDATE co_ordiniservizio_pianificazionefatture SET iddocumento=0 WHERE iddocumento='.prepare($id_record));

        flash()->info(tr('Fattura eliminata!'));

        break;

    // Duplicazione fattura
    case 'copy':
        if ($id_record) {
            // Duplicazione righe
            $righe = $dbo->fetchArray('SELECT * FROM co_righe_documenti WHERE iddocumento='.prepare($id_record));

            // Lettura dati fattura attuale
            $rs = $dbo->fetchArray('SELECT * FROM co_documenti WHERE id='.prepare($id_record));

            $id_segment = $rs[0]['id_segment'];

            // Calcolo prossimo numero fattura
            $numero = get_new_numerofattura(date('Y-m-d'));

            if ($dir == 'entrata') {
                $numero_esterno = get_new_numerosecondariofattura(date('Y-m-d'));
            } else {
                $numero_esterno = '';
            }

            // Duplicazione intestazione
            $dbo->query('INSERT INTO co_documenti(numero, numero_esterno, data, idanagrafica, idcausalet, idspedizione, idporto, idaspettobeni, idvettore, n_colli, idsede_partenza, idsede_destinazione, idtipodocumento, idstatodocumento, idpagamento, idconto, idrivalsainps, idritenutaacconto, rivalsainps, iva_rivalsainps, ritenutaacconto, bollo, note, note_aggiuntive, buono_ordine, id_segment) VALUES('.prepare($numero).', '.prepare($numero_esterno).', NOW(), '.prepare($rs[0]['idanagrafica']).', '.prepare($rs[0]['idcausalet']).', '.prepare($rs[0]['idspedizione']).', '.prepare($rs[0]['idporto']).', '.prepare($rs[0]['idaspettobeni']).', '.prepare($rs[0]['idvettore']).', '.prepare($rs[0]['n_colli']).', '.prepare($rs[0]['idsede_partenza']).', '.prepare($rs[0]['idsede_destinazione']).', '.prepare($rs[0]['idtipodocumento']).', (SELECT id FROM co_statidocumento WHERE descrizione=\'Bozza\'), '.prepare($rs[0]['idpagamento']).', '.prepare($rs[0]['idconto']).', '.prepare($rs[0]['idrivalsainps']).', '.prepare($rs[0]['idritenutaacconto']).', '.prepare($rs[0]['rivalsainps']).', '.prepare($rs[0]['iva_rivalsainps']).', '.prepare($rs[0]['ritenutaacconto']).', '.prepare($rs[0]['bollo']).', '.prepare($rs[0]['note']).', '.prepare($rs[0]['note_aggiuntive']).', '.prepare($rs[0]['buono_ordine']).', '.prepare($rs[0]['id_segment']).')');
            $id_record = $dbo->lastInsertedID();

            // TODO: sistemare la duplicazione delle righe generiche e degli articoli, ignorando interventi, ddt, ordini, preventivi
            foreach ($righe as $riga) {
                // Scarico/carico nuovamente l'articolo da magazzino
                if (!empty($riga['idarticolo'])) {
                    add_articolo_infattura($id_record, $riga['idarticolo'], $riga['descrizione'], $riga['idiva'], $riga['qta'], $riga['subtotale'], $riga['sconto'], $riga['sconto_unitario'], $riga['tipo_sconto'], $riga['idintervento'], $riga['idconto'], $riga['um']);
                } else {
                    $dbo->query('INSERT INTO co_righe_documenti(iddocumento, idordine, idddt, idintervento, idarticolo, idpreventivo, idcontratto, is_descrizione, idtecnico, idagente, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, idritenutaacconto, ritenutaacconto, idrivalsainps, rivalsainps, um, qta, `order`) VALUES('.prepare($id_record).', 0, 0, 0, '.prepare($riga['idarticolo']).', '.prepare($riga['idpreventivo']).', '.prepare($riga['idcontratto']).', '.prepare($riga['is_descrizione']).', '.prepare($riga['idtecnico']).', '.prepare($riga['idagente']).', '.prepare($riga['idconto']).', '.prepare($riga['idiva']).', '.prepare($riga['desc_iva']).', '.prepare($riga['iva']).', '.prepare($riga['iva_indetraibile']).', '.prepare($riga['descrizione']).', '.prepare($riga['subtotale']).', '.prepare($riga['sconto']).', '.prepare($riga['sconto_unitario']).', '.prepare($riga['tipo_sconto']).', '.prepare($riga['idritenutaacconto']).', '.prepare($riga['ritenutaacconto']).', '.prepare($riga['idrivalsainps']).', '.prepare($riga['rivalsainps']).', '.prepare($riga['um']).', '.prepare($riga['qta']).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))');
                }
            }

            // Ricalcolo inps, ritenuta e bollo (se la fattura non è stata pagata)
            ricalcola_costiagg_fattura($id_record);
            aggiorna_sedi_movimenti('documenti', $id_record);

            flash()->info(tr('Fattura duplicata correttamente!'));
        }

        break;

    case 'reopen':
        if (!empty($id_record)) {
            if ($dbo->query("UPDATE co_documenti SET idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione='Bozza') WHERE id=".prepare($id_record))) {
                elimina_scadenza($id_record);
                elimina_movimento($id_record, 1);
                ricalcola_costiagg_fattura($id_record);
                flash()->info(tr('Fattura riaperta!'));
            }
        }

        break;

    case 'addintervento':
        $id_intervento = post('idintervento');
        if (!empty($id_record) && $id_intervento !== null) {
            $copia_descrizione = post('copia_descrizione');
            $intervento = $dbo->fetchOne('SELECT descrizione FROM in_interventi WHERE id = '.prepare($id_intervento));
            if (!empty($copia_descrizione) && !empty($intervento['descrizione'])) {
                $riga = Descrizione::build($fattura);
                $riga->descrizione = $intervento['descrizione'];
                $riga->idintervento = $id_intervento;
                $riga->save();
            }

            aggiungi_intervento_in_fattura($id_intervento, $id_record, post('descrizione'), post('idiva'), post('idconto'), post('id_rivalsa_inps'), post('id_ritenuta_acconto'), post('calcolo_ritenuta_acconto'));

            flash()->info(tr('Intervento _NUM_ aggiunto!', [
                '_NUM_' => $idintervento,
            ]));
        }
        break;

    case 'manage_documento_fe':
        $data = Filter::getPOST();

        $ignore = [
            'id_plugin',
            'id_module',
            'id_record',
            'backto',
            'hash',
            'op',
            'idriga',
            'dir',
        ];
        foreach ($ignore as $name) {
            unset($data[$name]);
        }

        $fattura->dati_aggiuntivi_fe = $data;
        $fattura->save();

        flash()->info(tr('Dati FE aggiornati correttamente!'));

        break;

    case 'manage_riga_fe':
        $id_riga = post('idriga');
        if ($id_riga != null) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Descrizione::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            $data = Filter::getPOST();

            $ignore = [
                'id_plugin',
                'id_module',
                'id_record',
                'backto',
                'hash',
                'op',
                'idriga',
                'dir',
            ];
            foreach ($ignore as $name) {
                unset($data[$name]);
            }

            $riga->dati_aggiuntivi_fe = $data;
            $riga->save();

            flash()->info(tr('Dati FE aggiornati correttamente!'));
        }

        break;

    case 'manage_articolo':
        if (post('idriga') != null) {
            $articolo = Articolo::find(post('idriga'));
        } else {
            $originale = ArticoloOriginale::find(post('idarticolo'));
            $articolo = Articolo::build($fattura, $originale);
        }

        $qta = post('qta');
        if (!empty($record['is_reversed'])) {
            $qta = -$qta;
        }

        $articolo->descrizione = post('descrizione');
        $articolo->um = post('um') ?: null;

        $articolo->id_iva = post('idiva');
        $articolo->idconto = post('idconto');

        $articolo->calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto') ?: null;
        $articolo->id_ritenuta_acconto = post('id_ritenuta_acconto') ?: null;
        $articolo->ritenuta_contributi = boolval(post('ritenuta_contributi'));
        $articolo->id_rivalsa_inps = post('id_rivalsa_inps') ?: null;

        $articolo->prezzo_unitario_acquisto = post('prezzo_acquisto') ?: 0;
        $articolo->prezzo_unitario_vendita = post('prezzo');
        $articolo->sconto_unitario = post('sconto');
        $articolo->tipo_sconto = post('tipo_sconto');

        try {
            $articolo->qta = $qta;
        } catch (UnexpectedValueException $e) {
            flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
        }

        // Informazioni aggiuntive FE
        $articolo->data_inizio_periodo = post('data_inizio_periodo') ?: null;
        $articolo->data_fine_periodo = post('data_fine_periodo') ?: null;
        $articolo->riferimento_amministrazione = post('riferimento_amministrazione');
        $articolo->tipo_cessione_prestazione = post('tipo_cessione_prestazione');

        $articolo->save();

        aggiorna_sedi_movimenti('documenti', $id_record);
        if (post('idriga') != null) {
            flash()->info(tr('Articolo modificato!'));
        } else {
            flash()->info(tr('Articolo aggiunto!'));
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_fattura($id_record);

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($fattura);
        }

        $sconto->descrizione = post('descrizione');

        $sconto->id_iva = post('idiva');
        $sconto->idconto = post('idconto');

        $sconto->calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto') ?: null;
        $sconto->id_ritenuta_acconto = post('id_ritenuta_acconto') ?: null;
        $sconto->ritenuta_contributi = boolval(post('ritenuta_contributi'));
        $sconto->id_rivalsa_inps = post('id_rivalsa_inps') ?: null;

        $sconto->sconto_unitario = post('sconto_unitario');
        $sconto->tipo_sconto = 'UNT';

        $sconto->save();

        if (post('idriga') != null) {
            flash()->info(tr('Sconto/maggiorazione modificato!'));
        } else {
            flash()->info(tr('Sconto/maggiorazione aggiunto!'));
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_fattura($id_record);

        break;

    case 'manage_riga':
        if (post('idriga') != null) {
            $riga = Riga::find(post('idriga'));
        } else {
            $riga = Riga::build($fattura);
        }

        $qta = post('qta');
        if (!empty($record['is_reversed'])) {
            $qta = -$qta;
        }

        $riga->descrizione = post('descrizione');
        $riga->um = post('um') ?: null;

        $riga->id_iva = post('idiva');
        $riga->idconto = post('idconto');

        $riga->calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto') ?: null;
        $riga->id_ritenuta_acconto = post('id_ritenuta_acconto') ?: null;
        $riga->ritenuta_contributi = boolval(post('ritenuta_contributi'));
        $riga->id_rivalsa_inps = post('id_rivalsa_inps') ?: null;

        $riga->prezzo_unitario_acquisto = post('prezzo_acquisto') ?: 0;
        $riga->prezzo_unitario_vendita = post('prezzo');
        $riga->sconto_unitario = post('sconto');
        $riga->tipo_sconto = post('tipo_sconto');

        $riga->qta = $qta;

        // Informazioni aggiuntive FE
        $riga->data_inizio_periodo = post('data_inizio_periodo') ?: null;
        $riga->data_fine_periodo = post('data_fine_periodo') ?: null;
        $riga->riferimento_amministrazione = post('riferimento_amministrazione');
        $riga->tipo_cessione_prestazione = post('tipo_cessione_prestazione');

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga modificata!'));
        } else {
            flash()->info(tr('Riga aggiunta!'));
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_fattura($id_record);

        break;

    case 'manage_descrizione':
        if (post('idriga') != null) {
            $riga = Descrizione::find(post('idriga'));
        } else {
            $riga = Descrizione::build($fattura);
        }

        $riga->descrizione = post('descrizione');

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga descrittiva modificata!'));
        } else {
            flash()->info(tr('Riga descrittiva aggiunta!'));
        }

        break;

    // Scollegamento intervento da documento
    case 'unlink_intervento':
        if (!empty($id_record) && post('idriga') !== null) {
            $idriga = post('idriga');

            // Lettura preventivi collegati
            $query = 'SELECT iddocumento, idintervento FROM co_righe_documenti WHERE id='.prepare($idriga);
            $rsp = $dbo->fetchArray($query);
            $id_record = $rsp[0]['iddocumento'];
            $idintervento = $rsp[0]['idintervento'];

            // Ricalcolo inps, ritenuta e bollo
            if ($dir == 'entrata') {
                ricalcola_costiagg_fattura($id_record);
            } else {
                ricalcola_costiagg_fattura($id_record);
            }

            // Lettura interventi collegati
            // $query = 'SELECT id, idintervento FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idintervento IS NOT NULL';
            // $rs = $dbo->fetchArray($query);

            // Se ci sono degli interventi collegati li rimetto nello stato "Completato"
            // for ($i = 0; $i < sizeof($rs); ++$i) {
            $dbo->query("UPDATE in_interventi SET idstatointervento = (SELECT idstatointervento FROM in_statiintervento WHERE descrizione = 'Completato') WHERE id=".prepare($idintervento));

            // Rimuovo dalla fattura gli articoli collegati all'intervento
            $rs2 = $dbo->fetchArray('SELECT idarticolo FROM mg_articoli_interventi WHERE idintervento='.prepare($idintervento));
            for ($j = 0; $j < sizeof($rs2); ++$j) {
                rimuovi_articolo_dafattura($rs[0]['idarticolo'], $id_record, $rs[0]['idrigadocumento']);
            }
            // }

            // rimuovo riga da co_righe_documenti
            $query = 'DELETE FROM `co_righe_documenti` WHERE iddocumento='.prepare($id_record).' AND id='.prepare($idriga);
            $dbo->query($query);

            flash()->info(tr('Intervento _NUM_ rimosso!', [
                '_NUM_' => $idintervento,
            ]));
        }
        break;

    // Scollegamento articolo da documento
    case 'unlink_articolo':
        if (!empty($id_record)) {
            $idriga = post('idriga');

            if (!rimuovi_riga_fattura($id_record, $idriga, $dir)) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));

                return;
            }

            // Ricalcolo inps, ritenuta e bollo
            if ($dir == 'entrata') {
                ricalcola_costiagg_fattura($id_record);
            } else {
                ricalcola_costiagg_fattura($id_record);
            }

            aggiorna_sedi_movimenti('documenti', $id_record);
            flash()->info(tr('Articolo rimosso!'));
        }
        break;

    // Scollegamento preventivo da documento
    case 'unlink_preventivo':
        if (post('idriga') !== null) {
            $idriga = post('idriga');

            // Lettura preventivi collegati
            $query = 'SELECT iddocumento, idpreventivo, is_preventivo, idarticolo, qta FROM co_righe_documenti WHERE id='.prepare($idriga);
            $rsp = $dbo->fetchArray($query);
            $id_record = $rsp[0]['iddocumento'];
            $idpreventivo = $rsp[0]['idpreventivo'];
            $is_preventivo = $rsp[0]['is_preventivo'];
            $idarticolo = $rsp[0]['idarticolo'];
            $qta = $rsp[0]['qta'];

            // preventivo su unica riga, perdo il riferimento dell'articolo quindi lo vado a leggere da co_righe_preventivi
            if (empty($idarticolo) && $is_preventivo) {
                // rimetto a magazzino gli articoli collegati al preventivo
                $rsa = $dbo->fetchArray('SELECT idarticolo, qta FROM co_righe_preventivi WHERE idpreventivo = '.prepare($idpreventivo));
                for ($i = 0; $i < sizeof($rsa); ++$i) {
                    if (!empty($rsa[$i]['idarticolo'])) {
                        add_movimento_magazzino($rsa[$i]['idarticolo'], $rsa[$i]['qta'], ['iddocumento' => $id_record]);
                    }

                    // Ripristino le quantità da evadere nel preventivo
                    $dbo->update('co_righe_preventivi',
                        [
                            'qta_evasa' => 0,
                        ],
                        [
                            'idpreventivo' => $idpreventivo,
                        ]
                    );
                }
            } else {
                $rs5 = $dbo->fetchArray('SELECT idarticolo, id, qta, descrizione FROM co_righe_documenti WHERE  id = '.prepare($idriga));

                if (!empty($idarticolo)) {
                    rimuovi_articolo_dafattura($rs5[0]['idarticolo'], $id_record, $idriga);
                }

                // Ripristino le quantità da evadere nel preventivo
                $dbo->update('co_righe_preventivi',
                    [
                        'qta_evasa' => 0,
                    ],
                    [
                        'idarticolo' => $rs5[0]['idarticolo'],
                        'descrizione' => $rs5[0]['descrizione'],
                        'idpreventivo' => $idpreventivo,
                    ]
                );
            }

            $query = 'DELETE FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND id='.prepare($idriga);
            $dbo->query($query);

            $rs_righe = $dbo->fetchArray('SELECT * FROM co_righe_documenti WHERE idpreventivo='.prepare($idpreventivo));

            if (sizeof($rs_righe) == 0) {
                // Se ci sono dei preventivi collegati li rimetto nello stato "In attesa di pagamento"
                for ($i = 0; $i < sizeof($rsp); ++$i) {
                    $dbo->query("UPDATE co_preventivi SET idstato=(SELECT id FROM co_statipreventivi WHERE descrizione='In lavorazione') WHERE id=".prepare($rsp[$i]['idpreventivo']));

                    // Aggiorno anche lo stato degli interventi collegati ai preventivi
                    $dbo->query("UPDATE in_interventi SET idstatointervento = (SELECT idstatointervento FROM in_statiintervento WHERE descrizione = 'Completato') WHERE id_preventivo=".prepare($rsp[$i]['idpreventivo']));
                }

                /*
                    Rimuovo tutti gli articoli dalla fattura collegati agli interventi di questo preventivo
                */
                $rs2 = $dbo->fetchArray('SELECT id FROM in_interventi WHERE id_preventivo = '.prepare($idpreventivo));
                for ($i = 0; $i < sizeof($rs2); ++$i) {
                    // Leggo gli articoli usati in questo intervento
                    $rs3 = $dbo->fetchArray('SELECT idarticolo FROM mg_articoli_interventi WHERE idintervento='.prepare($rs2[$i]['id']));
                    for ($j = 0; $j < sizeof($rs3); ++$j) {
                        // Leggo l'id della riga in fattura di questo articolo
                        $rs4 = $dbo->fetchArray('SELECT id FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idarticolo='.prepare($rs3[$j]['idarticolo']));
                        for ($x = 0; $x < sizeof($rs4); ++$x) {
                            rimuovi_articolo_dafattura($rs3[$j]['idarticolo'], $id_record, $rs4[$x]['id']);
                        }
                    }
                }
            }

            // Ricalcolo inps, ritenuta e bollo
            if ($dir == 'entrata') {
                ricalcola_costiagg_fattura($id_record);
            } else {
                ricalcola_costiagg_fattura($id_record);
            }

            flash()->info(tr('Preventivo rimosso!'));
        }
        break;

    // Scollegamento contratto da documento
    case 'unlink_contratto':
        if (post('idriga') !== null) {
            $idriga = post('idriga');

            // Lettura contratti collegati
            $query = 'SELECT iddocumento, idcontratto, is_contratto, idarticolo FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idcontratto IS NOT NULL AND NOT idcontratto=0';
            $rsp = $dbo->fetchArray($query);
            $id_record = $rsp[0]['iddocumento'];
            $idcontratto = $rsp[0]['idcontratto'];
            $is_contratto = $rsp[0]['is_contratto'];
            $idarticolo = $rsp[0]['idarticolo'];

            // contratto su unica riga, perdo il riferimento dell'articolo quindi lo vado a leggere da co_righe_contratti
            if (empty($idarticolo) && $is_contratto) {
                // rimetto a magazzino gli articoli collegati al contratto
                $rsa = $dbo->fetchArray('SELECT idarticolo, qta FROM co_righe_contratti WHERE idcontratto = '.prepare($idcontratto));
                for ($i = 0; $i < sizeof($rsa); ++$i) {
                    if (!empty($rsa[$i]['idarticolo'])) {
                        add_movimento_magazzino($rsa[$i]['idarticolo'], $rsa[$i]['qta'], ['iddocumento' => $id_record]);
                    }

                    // Ripristino le quantità da evadere nel contratto
                    $dbo->update('co_righe_contratti',
                        [
                            'qta_evasa' => 0,
                        ],
                        [
                            'idcontratto' => $idcontratto,
                        ]
                    );
                }
            } else {
                $rs5 = $dbo->fetchArray('SELECT idarticolo, id, qta, descrizione FROM co_righe_documenti WHERE  id = '.prepare($idriga).'  AND idintervento IS NULL');
                if (!empty($idarticolo)) {
                    rimuovi_articolo_dafattura($rs5[0]['idarticolo'], $id_record, $idriga);
                }

                // Ripristino le quantità da evadere nel contratto
                $dbo->update('co_righe_contratti',
                    [
                        'qta_evasa' => 0,
                    ],
                    [
                        'idarticolo' => $rs5[0]['idarticolo'],
                        'descrizione' => $rs5[0]['descrizione'],
                        'idcontratto' => $idcontratto,
                    ]
                );
            }

            $query = 'DELETE FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idcontratto='.prepare($idcontratto);

            if ($dbo->query($query)) {
                // Se ci sono dei preventivi collegati li rimetto nello stato "In attesa di pagamento"
                for ($i = 0; $i < sizeof($rsp); ++$i) {
                    $dbo->query("UPDATE co_contratti SET idstato=(SELECT id FROM co_staticontratti WHERE descrizione='In lavorazione') WHERE id=".prepare($rsp[$i]['idcontratto']));

                    // Aggiorno anche lo stato degli interventi collegati ai contratti
                    $dbo->query("UPDATE in_interventi SET idstatointervento = (SELECT idstatointervento FROM in_statiintervento WHERE descrizione = 'Completato') WHERE id IN (SELECT idintervento FROM co_promemoria WHERE idcontratto=".prepare($rsp[$i]['idcontratto']).')');
                }

                /*
                    Rimuovo tutti gli articoli dalla fattura collegati agli interventi che sono collegati a questo contratto
                */
                $rs2 = $dbo->fetchArray('SELECT idintervento FROM co_promemoria WHERE idcontratto='.prepare($idcontratto)." AND NOT idcontratto=''");
                for ($i = 0; $i < sizeof($rs2); ++$i) {
                    // Leggo gli articoli usati in questo intervento
                    $rs3 = $dbo->fetchArray('SELECT idarticolo FROM mg_articoli_interventi WHERE idintervento='.prepare($rs2[$i]['idintervento']));
                    for ($j = 0; $j < sizeof($rs3); ++$j) {
                        // Leggo l'id della riga in fattura di questo articolo
                        $rs4 = $dbo->fetchArray('SELECT id FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idarticolo='.prepare($rs3[$j]['idarticolo']));
                        for ($x = 0; $x < sizeof($rs4); ++$x) {
                            rimuovi_articolo_dafattura($rs3[$j]['idarticolo'], $id_record, $rs4[$x]['id']);
                        }
                    }
                }

                // Ricalcolo inps, ritenuta e bollo
                if ($dir == 'entrata') {
                    ricalcola_costiagg_fattura($id_record);
                } else {
                    ricalcola_costiagg_fattura($id_record);
                }

                flash()->info(tr('Contratto rimosso!'));
            }
        }
        break;

    // Scollegamento riga generica da documento
    case 'unlink_riga':
        if (post('idriga') !== null) {
            $idriga = post('idriga');

            rimuovi_riga_fattura($id_record, $idriga, $dir);

            // Ricalcolo inps, ritenuta e bollo
            if ($dir == 'entrata') {
                ricalcola_costiagg_fattura($id_record);
            } else {
                ricalcola_costiagg_fattura($id_record);
            }

            flash()->info(tr('Riga rimossa!'));
        }
        break;

    case 'add_serial':
        $articolo = Articolo::find(post('idriga'));

        $serials = (array) post('serial');

        $articolo->serials = $serials;
        $articolo->save();

        break;

    case 'update_position':
        $orders = explode(',', $_POST['order']);
        $order = 0;

        foreach ($orders as $idriga) {
            $dbo->query('UPDATE `co_righe_documenti` SET `order`='.prepare($order).' WHERE id='.prepare($idriga));
            ++$order;
        }

        break;

    // Aggiunta di un ordine in fattura
    case 'add_ordine':
        $ordine = \Modules\Ordini\Ordine::find(post('id_ordine'));

        // Creazione della fattura al volo
        if (post('create_document') == 'on') {
            $descrizione = ($dir == 'entrata') ? 'Fattura immediata di vendita' : 'Fattura immediata di acquisto';
            $tipo = Tipo::where('descrizione', $descrizione)->first();

            $fattura = Fattura::build($ordine->anagrafica, $tipo, post('data'), post('id_segment'));
            $fattura->idpagamento = $ordine->idpagamento;
            $fattura->save();

            $id_record = $fattura->id;
        }

        $id_rivalsa_inps = setting('Percentuale rivalsa');
        if ($dir == 'uscita') {
            $id_ritenuta_acconto = $fattura->anagrafica->id_ritenuta_acconto_acquisti;
        } else {
            $id_ritenuta_acconto = $fattura->anagrafica->id_ritenuta_acconto_vendite ?: setting("Percentuale ritenuta d'acconto");
        }
        $calcolo_ritenuta_acconto = setting("Metodologia calcolo ritenuta d'acconto predefinito");
        $id_conto = post('id_conto');

        $parziale = false;
        $righe = $ordine->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on') {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($fattura, $qta);
                $copia->id_conto = $id_conto;

                $copia->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
                $copia->id_ritenuta_acconto = $id_ritenuta_acconto;
                $copia->id_rivalsa_inps = $id_rivalsa_inps;

                // Aggiornamento seriali dalla riga dell'ordine
                if ($copia->isArticolo()) {
                    $copia->movimenta($copia->qta);

                    $serials = is_array(post('serial')[$riga->id]) ? post('serial')[$riga->id] : [];

                    $copia->serials = $serials;
                }

                $copia->save();
            }

            if ($riga->qta != $riga->qta_evasa) {
                $parziale = true;
            }
        }

        // Impostazione del nuovo stato
        $descrizione = $parziale ? 'Parzialmente fatturato' : 'Fatturato';
        $stato = \Modules\Ordini\Stato::where('descrizione', $descrizione)->first();
        $ordine->stato()->associate($stato);
        $ordine->save();

        ricalcola_costiagg_fattura($id_record);
        aggiorna_sedi_movimenti('documenti', $id_record);

        flash()->info(tr('Ordine _NUM_ aggiunto!', [
            '_NUM_' => $ordine->numero,
        ]));

        break;

    // Aggiunta di un ddt in fattura
    case 'add_ddt':
        $ddt = \Modules\DDT\DDT::find(post('id_ddt'));

        // Creazione della fattura al volo
        if (post('create_document') == 'on') {
            $descrizione = ($dir == 'entrata') ? 'Fattura differita di vendita' : 'Fattura differita di acquisto';
            $tipo = Tipo::where('descrizione', $descrizione)->first();

            $fattura = Fattura::build($ddt->anagrafica, $tipo, post('data'), post('id_segment'));
            $fattura->idpagamento = $ddt->idpagamento;
            $fattura->save();

            $id_record = $fattura->id;
        }

        $id_rivalsa_inps = setting('Percentuale rivalsa');
        if ($dir == 'uscita') {
            $id_ritenuta_acconto = $fattura->anagrafica->id_ritenuta_acconto_acquisti;
        } else {
            $id_ritenuta_acconto = $fattura->anagrafica->id_ritenuta_acconto_vendite ?: setting("Percentuale ritenuta d'acconto");
        }
        $calcolo_ritenuta_acconto = setting("Metodologia calcolo ritenuta d'acconto predefinito");
        $id_conto = post('id_conto');

        $parziale = false;
        $righe = $ddt->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on') {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($fattura, $qta);
                $copia->id_conto = $id_conto;

                $copia->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
                $copia->id_ritenuta_acconto = $id_ritenuta_acconto;
                $copia->id_rivalsa_inps = $id_rivalsa_inps;

                // Aggiornamento seriali dalla riga dell'ordine
                if ($copia->isArticolo()) {
                    $serials = is_array(post('serial')[$riga->id]) ? post('serial')[$riga->id] : [];

                    $copia->serials = $serials;
                }

                $copia->save();
            }

            if ($riga->qta != $riga->qta_evasa) {
                $parziale = true;
            }
        }

        // Impostazione del nuovo stato
        $descrizione = $parziale ? 'Parzialmente fatturato' : 'Fatturato';
        $stato = \Modules\DDT\Stato::where('descrizione', $descrizione)->first();
        $ddt->stato()->associate($stato);
        $ddt->save();

        ricalcola_costiagg_fattura($id_record);
        aggiorna_sedi_movimenti('documenti', $id_record);

        flash()->info(tr('DDT _NUM_ aggiunto!', [
            '_NUM_' => $ddt->numero,
        ]));

        break;

    // Aggiunta di un preventivo in fattura
    case 'add_preventivo':
        $preventivo = \Modules\Preventivi\Preventivo::find(post('id_preventivo'));

        // Creazione della fattura al volo
        if (post('create_document') == 'on') {
            $tipo = Tipo::where('descrizione', 'Fattura immediata di vendita')->first();

            $fattura = Fattura::build($preventivo->anagrafica, $tipo, post('data'), post('id_segment'));
            $fattura->idpagamento = $preventivo->idpagamento;
            $fattura->save();

            $id_record = $fattura->id;
        }

        $id_rivalsa_inps = setting('Percentuale rivalsa');
        if ($dir == 'uscita') {
            $id_ritenuta_acconto = $fattura->anagrafica->id_ritenuta_acconto_acquisti;
        } else {
            $id_ritenuta_acconto = $fattura->anagrafica->id_ritenuta_acconto_vendite ?: setting("Percentuale ritenuta d'acconto");
        }
        $calcolo_ritenuta_acconto = setting("Metodologia calcolo ritenuta d'acconto predefinito");
        $id_conto = post('id_conto');

        $parziale = false;
        $righe = $preventivo->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on') {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($fattura, $qta);
                $copia->id_conto = $id_conto;

                $copia->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
                $copia->id_ritenuta_acconto = $id_ritenuta_acconto;
                $copia->id_rivalsa_inps = $id_rivalsa_inps;

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

        // Impostazione del nuovo stato
        $descrizione = $parziale ? 'Parzialmente fatturato' : 'Fatturato';
        $stato = \Modules\Preventivi\Stato::where('descrizione', $descrizione)->first();
        $preventivo->stato()->associate($stato);
        $preventivo->save();

        // Trasferimento degli interventi collegati
        $interventi = $preventivo->interventi;
        $stato_intervento = \Modules\Interventi\Stato::where('descrizione', 'Fatturato')->first();
        foreach ($interventi as $intervento) {
            $intervento->stato()->associate($stato_intervento);
            $intervento->save();
        }

        ricalcola_costiagg_fattura($id_record);
        aggiorna_sedi_movimenti('documenti', $id_record);

        flash()->info(tr('Preventivo _NUM_ aggiunto!', [
            '_NUM_' => $preventivo->numero,
        ]));

        break;

    // Aggiunta di un contratto in fattura
    case 'add_contratto':
        $contratto = \Modules\Contratti\Contratto::find(post('id_contratto'));

        // Creazione della fattura al volo
        if (post('create_document') == 'on') {
            $tipo = Tipo::where('descrizione', 'Fattura immediata di vendita')->first();

            $fattura = Fattura::build($contratto->anagrafica, $tipo, post('data'), post('id_segment'));
            $fattura->idpagamento = $contratto->idpagamento;
            $fattura->save();

            $id_record = $fattura->id;
        }

        $id_rivalsa_inps = setting('Percentuale rivalsa');
        if ($dir == 'uscita') {
            $id_ritenuta_acconto = $fattura->anagrafica->id_ritenuta_acconto_acquisti;
        } else {
            $id_ritenuta_acconto = $fattura->anagrafica->id_ritenuta_acconto_vendite ?: setting("Percentuale ritenuta d'acconto");
        }
        $calcolo_ritenuta_acconto = setting("Metodologia calcolo ritenuta d'acconto predefinito");
        $id_conto = post('id_conto');

        $parziale = false;
        $righe = $contratto->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on') {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($fattura, $qta);
                $copia->id_conto = $id_conto;

                $copia->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
                $copia->id_ritenuta_acconto = $id_ritenuta_acconto;
                $copia->id_rivalsa_inps = $id_rivalsa_inps;

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

        // Impostazione del nuovo stato
        $descrizione = $parziale ? 'Parzialmente fatturato' : 'Fatturato';
        $stato = \Modules\Contratti\Stato::where('descrizione', $descrizione)->first();
        $contratto->stato()->associate($stato);
        $contratto->save();

        // Trasferimento degli interventi collegati
        $interventi = $contratto->interventi;
        $stato_intervento = \Modules\Interventi\Stato::where('descrizione', 'Fatturato')->first();
        foreach ($interventi as $intervento) {
            $intervento->stato()->associate($stato_intervento);
            $intervento->save();
        }

        ricalcola_costiagg_fattura($id_record);
        aggiorna_sedi_movimenti('documenti', $id_record);

        flash()->info(tr('Contratto _NUM_ aggiunto!', [
            '_NUM_' => $contratto->numero,
        ]));

        break;

    // Nota di credito
    case 'nota_credito':
        $id_segment = post('id_segment');
        $data = post('data');

        $anagrafica = $fattura->anagrafica;
        $tipo = Tipo::where('descrizione', 'Nota di credito')->where('dir', 'entrata')->first();

        $nota = Fattura::build($anagrafica, $tipo, $data, $id_segment);
        $nota->ref_documento = $fattura->id;
        $nota->idconto = $fattura->idconto;
        $nota->idpagamento = $fattura->idpagamento;
        $nota->idbanca = $fattura->idbanca;
        $nota->idsede_partenza = $fattura->idsede_partenza;
        $nota->idsede_destinazione = $fattura->idsede_destinazione;
        $nota->save();

        $righe = $fattura->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on') {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($nota, -$qta);
                $copia->ref_riga_documento = $riga->id;

                // Aggiornamento seriali dalla riga dell'ordine
                if ($copia->isArticolo()) {
                    $copia->movimenta($copia->qta);

                    $serials = is_array(post('serial')[$riga->id]) ? post('serial')[$riga->id] : [];

                    $copia->serials = $serials;
                    $riga->removeSerials($serials);
                }

                $copia->save();
            }
        }

        $id_record = $nota->id;
        aggiorna_sedi_movimenti('documenti', $id_record);

        break;

    case 'transform':
        $fattura->id_segment = post('id_segment');
        $fattura->save();

        break;
}

// Nota di debito
if (get('op') == 'nota_addebito') {
    $rs_segment = $dbo->fetchArray("SELECT * FROM zz_segments WHERE predefined_addebito='1'");
    if (!empty($rs_segment)) {
        $id_segment = $rs_segment[0]['id'];
    } else {
        $id_segment = $record['id_segment'];
    }

    $anagrafica = $fattura->anagrafica;
    $tipo = Tipo::where('descrizione', 'Nota di debito')->where('dir', 'entrata')->first();
    $data = $fattura->data;

    $nota = Fattura::build($anagrafica, $tipo, $data, $id_segment);
    $nota->ref_documento = $fattura->id;
    $nota->idconto = $fattura->idconto;
    $nota->idpagamento = $fattura->idpagamento;
    $nota->idbanca = $fattura->idbanca;
    $nota->idsede_partenza = $fattura->idsede_partenza;
    $nota->idsede_destinazione = $fattura->idsede_destinazione;
    $nota->save();

    $id_record = $nota->id;
    aggiorna_sedi_movimenti('documenti', $id_record);
}

// Aggiornamento stato dei ddt presenti in questa fattura in base alle quantità totali evase
if (!empty($id_record) && setting('Cambia automaticamente stato ddt fatturati')) {
    $rs = $dbo->fetchArray('SELECT DISTINCT idddt FROM co_righe_documenti WHERE iddocumento='.prepare($id_record));

    for ($i = 0; $i < sizeof($rs); ++$i) {
        $dbo->query('UPDATE dt_ddt SET idstatoddt=(SELECT id FROM dt_statiddt WHERE descrizione="'.get_stato_ddt($rs[$i]['idddt']).'") WHERE id = '.prepare($rs[$i]['idddt']));
    }
}

// Aggiornamento stato degli ordini presenti in questa fattura in base alle quantità totali evase
if (!empty($id_record) && setting('Cambia automaticamente stato ordini fatturati')) {
    $rs = $dbo->fetchArray('SELECT DISTINCT idordine FROM co_righe_documenti WHERE iddocumento='.prepare($id_record));

    for ($i = 0; $i < sizeof($rs); ++$i) {
        $dbo->query('UPDATE or_ordini SET idstatoordine=(SELECT id FROM or_statiordine WHERE descrizione="'.get_stato_ordine($rs[$i]['idordine']).'") WHERE id = '.prepare($rs[$i]['idordine']));
    }
}
