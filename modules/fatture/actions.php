<?php

include_once __DIR__.'/../../core.php';

// Necessaria per la funzione add_movimento_magazzino
include_once $docroot.'/modules/articoli/modutil.php';
include_once $docroot.'/modules/interventi/modutil.php';
include_once $docroot.'/modules/ddt/modutil.php';
include_once $docroot.'/modules/ordini/modutil.php';

$module = Modules::get($id_module);

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $data = $post['data'];
        $dir = $post['dir'];
        $idtipodocumento = post('idtipodocumento');

        $numero = get_new_numerofattura($data);
        if ($dir == 'entrata') {
            $numero_esterno = get_new_numerosecondariofattura($data);
            $idconto = get_var('Conto predefinito fatture di vendita');
        } else {
            $numero_esterno = '';
            $idconto = get_var('Conto predefinito fatture di acquisto');
        }

        $campo = ($dir == 'entrata') ? 'idpagamento_vendite' : 'idpagamento_acquisti';

        // Tipo di pagamento predefinito dall'anagrafica
        $query = 'SELECT id FROM co_pagamenti WHERE id=(SELECT '.$campo.' AS pagamento FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica).')';
        $rs = $dbo->fetchArray($query);
        $idpagamento = $rs[0]['id'];

        // Se la fattura è di vendita e non è stato associato un pagamento predefinito al cliente leggo il pagamento dalle impostazioni
        if ($dir == 'entrata' && $idpagamento == '') {
            $idpagamento = get_var('Tipo di pagamento predefinito');
        }

        $query = 'INSERT INTO co_documenti (numero, numero_esterno, idanagrafica, idconto, idtipodocumento, idpagamento, data, idstatodocumento, idsede) VALUES ('.prepare($numero).', '.prepare($numero_esterno).', '.prepare($idanagrafica).', '.prepare($idconto).', '.prepare($idtipodocumento).', '.prepare($idpagamento).', '.prepare($data).", (SELECT `id` FROM `co_statidocumento` WHERE `descrizione`='Bozza'), (SELECT idsede_fatturazione FROM an_anagrafiche WHERE idanagrafica=".prepare($idanagrafica).') )';
        $dbo->query($query);
        $id_record = $dbo->lastInsertedID();

        $_SESSION['infos'][] = tr('Aggiunta fattura numero _NUM_!', [
            '_NUM_' => $numero,
        ]);

        break;

    case 'update':
        if (isset($post['id_record'])) {
            $numero_esterno = post('numero_esterno');
            $data = $post['data'];
            $idanagrafica = post('idanagrafica');
            $idagente = post('idagente');
            $note = post('note');
            $note_aggiuntive = post('note_aggiuntive');
            $idtipodocumento = post('idtipodocumento');
            $idstatodocumento = post('idstatodocumento');
            $idpagamento = post('idpagamento');
            $idcausalet = post('idcausalet');
            $idspedizione = post('idspedizione');
            $idporto = post('idporto');
            $idaspettobeni = post('idaspettobeni');
            $idvettore = post('idvettore');
            $n_colli = post('n_colli');
            $idsede = post('idsede');
            $idconto = post('idconto');
            $totale_imponibile = get_imponibile_fattura($id_record);
            $totale_fattura = get_totale_fattura($id_record);

            if ($dir == 'uscita') {
                $idrivalsainps = post('idrivalsainps');
                $idritenutaacconto = post('idritenutaacconto');
            } else {
                $idrivalsainps = 0;
                $idritenutaacconto = 0;
            }

            // Leggo la descrizione del pagamento
            $query = 'SELECT descrizione FROM co_pagamenti WHERE id='.prepare($idpagamento);
            $rs = $dbo->fetchArray($query);
            $pagamento = $rs[0]['descrizione'];

            // Query di aggiornamento
            $query = 'UPDATE co_documenti SET '.
                ' data='.prepare($data).','.
                ' idstatodocumento='.prepare($idstatodocumento).','.
                ' idtipodocumento='.prepare($idtipodocumento).','.
                ' idanagrafica='.prepare($idanagrafica).','.
                ' idagente='.prepare($idagente).','.
                ' idpagamento='.prepare($idpagamento).','.
                ' idcausalet='.prepare($idcausalet).','.
                ' idspedizione='.prepare($idspedizione).','.
                ' idporto='.prepare($idporto).','.
                ' idaspettobeni='.prepare($idaspettobeni).','.
                ' idvettore='.prepare($idvettore).','.
                ' n_colli='.prepare($n_colli).','.
                ' idsede='.prepare($idsede).','.
                ' numero_esterno='.prepare($numero_esterno).','.
                ' note='.prepare($note).','.
                ' note_aggiuntive='.prepare($note_aggiuntive).','.
                ' idconto='.prepare($idconto).','.
                ' idrivalsainps='.prepare($idrivalsainps).','.
                ' idritenutaacconto='.prepare($idritenutaacconto).','.
                ' bollo=0, rivalsainps=0, ritenutaacconto=0, iva_rivalsainps=0 '.
                ' WHERE id='.prepare($id_record);

            $dbo->query($query);
            $query = 'SELECT descrizione FROM co_statidocumento WHERE id='.prepare($idstatodocumento);
            $rs = $dbo->fetchArray($query);

            if ($records[0]['stato'] != 'Pagato' && $records[0]['stato'] != 'Emessa') {
                $tipo_sconto = $post['tipo_sconto_generico'];
                $sconto = $post['sconto_generico'];

                $dbo->update('co_documenti', [
                    'tipo_sconto_globale' => $tipo_sconto,
                    'sconto_globale' => $sconto,
                ], ['id' => $id_record]);

                aggiorna_sconto([
                    'parent' => 'co_documenti',
                    'row' => 'co_righe_documenti',
                ], [
                    'parent' => 'id',
                    'row' => 'iddocumento',
                ], $id_record);
            }

            // Ricalcolo inps, ritenuta e bollo (se la fattura non è stata pagata)
            if ($dir == 'entrata') {
                ricalcola_costiagg_fattura($id_record);
            } else {
                ricalcola_costiagg_fattura($id_record, $idrivalsainps, $idritenutaacconto, $bollo);
            }

            // Elimino la scadenza e tutti i movimenti, poi se la fattura è emessa le ricalcolo
            if ($rs[0]['descrizione'] == 'Bozza') {
                elimina_scadenza($id_record);
                elimina_movimento($id_record, 0);
                elimina_movimento($id_record, 1);
            } elseif ($rs[0]['descrizione'] == 'Emessa') {
                elimina_scadenza($id_record);
                elimina_movimento($id_record, 0);
            }

            // Se la fattura è in stato "Emessa" posso inserirla in scadenziario e aprire il mastrino cliente
            if ($rs[0]['descrizione'] == 'Emessa') {
                aggiungi_scadenza($id_record, $pagamento);
                aggiungi_movimento($id_record, $dir);
            }

            $_SESSION['infos'][] = tr('Fattura modificata correttamente!');
        }

        break;

    // eliminazione documento
    case 'delete':
        // Se ci sono degli articoli collegati (ma non collegati a preventivi o interventi) li rimetto nel magazzino
        $query = 'SELECT id, idarticolo FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND NOT idarticolo=0 AND idpreventivo=0 AND idintervento IS NULL';
        $rs = $dbo->fetchArray($query);

        foreach ($rs as $value) {
            $non_rimovibili = seriali_non_rimuovibili('id_riga_documento', $value['id'], $dir);
            if (!empty($non_rimovibili)) {
                $_SESSION['errors'][] = tr('Alcuni serial number sono già stati utilizzati!');

                return;
            }
        }

        for ($i = 0; $i < sizeof($rs); ++$i) {
            rimuovi_articolo_dafattura($rs[$i]['idarticolo'], $id_record, $rs[$i]['id']);
        }

        // Se ci sono dei preventivi collegati li rimetto nello stato "In attesa di pagamento"
        $query = 'SELECT idpreventivo FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idpreventivo IS NOT NULL';
        $rs = $dbo->fetchArray($query);

        for ($i = 0; $i < sizeof($rs); ++$i) {
            $dbo->query("UPDATE co_preventivi SET idstato=(SELECT id FROM co_statipreventivi WHERE descrizione='In lavorazione') WHERE id=".prepare($rs[$i]['idpreventivo']));
        }

        // Se ci sono degli interventi collegati li rimetto nello stato "Completato"
        $query = 'SELECT idintervento FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idintervento IS NOT NULL';
        $rs = $dbo->fetchArray($query);

        for ($i = 0; $i < sizeof($rs); ++$i) {
            $dbo->query("UPDATE in_interventi SET idstatointervento='OK' WHERE id=".prepare($rs[$i]['idintervento']));
        }

        // Se delle righe sono state create da un ordine, devo riportare la quantità evasa nella tabella degli ordini al valore di prima, riaggiungendo la quantità che sto togliendo
        $rs = $dbo->fetchArray('SELECT qta, descrizione, idarticolo, idordine, idiva FROM co_righe_documenti WHERE iddocumento='.prepare($id_record));
        foreach ($rs as $r) {
            $dbo->query('UPDATE or_righe_ordini SET qta_evasa=qta_evasa-'.$r['qta'].' WHERE descrizione='.prepare($r['descrizione']).' AND idarticolo='.prepare($r['idarticolo']).' AND idordine='.prepare($r['idordine']).' AND idiva='.prepare($r['idiva']));
        }

        // Se delle righe sono state create da un ddt, devo riportare la quantità evasa nella tabella dei ddt al valore di prima, riaggiungendo la quantità che sto togliendo
        $rs = $dbo->fetchArray('SELECT qta, descrizione, idarticolo, idddt, idiva FROM co_righe_documenti WHERE iddocumento='.prepare($id_record));
        foreach ($rs as $r) {
            $dbo->query('UPDATE dt_righe_ddt SET qta_evasa=qta_evasa-'.$r['qta'].' WHERE descrizione='.prepare($r['descrizione']).' AND idarticolo='.prepare($r['idarticolo']).' AND idddt='.prepare($r['idddt']).' AND idiva='.prepare($r['idiva']));
        }

        $dbo->query('DELETE FROM co_documenti WHERE id='.prepare($id_record));
        $dbo->query('DELETE FROM co_righe_documenti WHERE iddocumento='.prepare($id_record));
        $dbo->query('DELETE FROM co_scadenziario WHERE iddocumento='.prepare($id_record));
        $dbo->query('DELETE FROM mg_movimenti WHERE iddocumento='.prepare($id_record));

        // Azzeramento collegamento della rata contrattuale alla pianificazione
        $dbo->query('UPDATE co_ordiniservizio_pianificazionefatture SET iddocumento=0 WHERE iddocumento='.prepare($id_record));

        elimina_scadenza($id_record);
        elimina_movimento($id_record);

        $_SESSION['infos'][] = tr('Fattura eliminata!');

        break;

    // Duplicazione fattura
    case 'copy':
        if ($id_record) {
            // Calcolo prossimo numero fattura
            $numero = get_new_numerofattura(date('Y-m-d'));

            if ($dir == 'entrata') {
                $numero_esterno = get_new_numerosecondariofattura(date('Y-m-d'));
            } else {
                $numero_esterno = '';
            }

            // Duplicazione righe
            $righe = $dbo->fetchArray('SELECT * FROM co_righe_documenti WHERE iddocumento='.prepare($id_record));

            // Lettura dati fattura attuale
            $rs = $dbo->fetchArray('SELECT * FROM co_documenti WHERE id='.prepare($id_record));

            // Duplicazione intestazione
            $dbo->query('INSERT INTO co_documenti(numero, numero_esterno, data, idanagrafica, idcausalet, idspedizione, idporto, idaspettobeni, idvettore, n_colli, idsede, idtipodocumento, idstatodocumento, idpagamento, idconto, idrivalsainps, idritenutaacconto, rivalsainps, iva_rivalsainps, ritenutaacconto, bollo, note, note_aggiuntive, buono_ordine) VALUES('.prepare($numero).', '.prepare($numero_esterno).', '.prepare($rs[0]['data']).', '.prepare($rs[0]['idanagrafica']).', '.prepare($rs[0]['idcausalet']).', '.prepare($rs[0]['idspedizione']).', '.prepare($rs[0]['idporto']).', '.prepare($rs[0]['idaspettobeni']).', '.prepare($rs[0]['idvettore']).', '.prepare($rs[0]['n_colli']).', '.prepare($rs[0]['idsede']).', '.prepare($rs[0]['idtipodocumento']).', (SELECT id FROM co_statidocumento WHERE descrizione=\'Bozza\'), '.prepare($rs[0]['idpagamento']).', '.prepare($rs[0]['idconto']).', '.prepare($rs[0]['idrivalsainps']).', '.prepare($rs[0]['idritenutaacconto']).', '.prepare($rs[0]['rivalsainps']).', '.prepare($rs[0]['iva_rivalsainps']).', '.prepare($rs[0]['ritenutaacconto']).', '.prepare($rs[0]['bollo']).', '.prepare($rs[0]['note']).', '.prepare($rs[0]['note_aggiuntive']).', '.prepare($rs[0]['buono_ordine']).')');
            $id_record = $dbo->lastInsertedID();

            // TODO: sistemare la duplicazione delle righe generiche e degli articoli, ingorando interventi, ddt, ordini, preventivi
            foreach ($righe as $riga) {
                // Scarico/carico nuovamente l'articolo da magazzino
                if (!empty($riga['idarticolo'])) {
                    add_articolo_infattura($id_record, $riga['idarticolo'], $riga['descrizione'], $riga['idiva'], $riga['qta'], $riga['subtotale'], $riga['sconto'], $riga['sconto_unitario'], $riga['tipo_sconto'], $riga['idintervento'], $riga['idconto'], $riga['um']);
                } else {
                    $dbo->query('INSERT INTO co_righe_documenti(iddocumento, idordine, idddt, idintervento, idarticolo, idpreventivo, idcontratto, idtecnico, idagente, idautomezzo, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, idritenutaacconto, ritenutaacconto, idrivalsainps, rivalsainps, um, qta, `order`) VALUES('.prepare($id_record).', 0, 0, 0, '.prepare($riga['idarticolo']).', '.prepare($riga['idpreventivo']).', '.prepare($riga['idcontratto']).', '.prepare($riga['idtecnico']).', '.prepare($riga['idagente']).', '.prepare($riga['idautomezzo']).', '.prepare($riga['idconto']).', '.prepare($riga['idiva']).', '.prepare($riga['desc_iva']).', '.prepare($riga['iva']).', '.prepare($riga['iva_indetraibile']).', '.prepare($riga['descrizione']).', '.prepare($riga['subtotale']).', '.prepare($riga['sconto']).', '.prepare($riga['sconto_unitario']).', '.prepare($riga['tipo_sconto']).', '.prepare($riga['idritenutaacconto']).', '.prepare($riga['ritenutaacconto']).', '.prepare($riga['idrivalsainps']).', '.prepare($riga['rivalsainps']).', '.prepare($riga['um']).', '.prepare($riga['qta']).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))');
                }
            }

            // Ricalcolo inps, ritenuta e bollo (se la fattura non è stata pagata)
            if ($dir == 'entrata') {
                ricalcola_costiagg_fattura($id_record);
            } else {
                ricalcola_costiagg_fattura($id_record, $rs[0]['idrivalsainps'], $rs[0]['idritenutaacconto'], $rs[0]['bollo']);
            }

            $_SESSION['infos'][] = tr('Fattura duplicata correttamente!');
        }

        break;

    case 'reopen':
        if (!empty($id_record)) {
            if ($dbo->query("UPDATE co_documenti SET idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione='Bozza') WHERE id=".prepare($id_record))) {
                elimina_scadenza($id_record);
                elimina_movimento($id_record, 1);
                ricalcola_costiagg_fattura($id_record);
                $_SESSION['infos'][] = tr('Fattura riaperta!');
            }
        }

        break;

    case 'addintervento':
        if (!empty($id_record) && isset($post['idintervento'])) {
            $idintervento = post('idintervento');
            $descrizione = post('descrizione');
            $idiva = post('idiva');
            $idconto = post('idconto');

            // Leggo l'anagrafica del cliente
            $rs = $dbo->fetchArray('SELECT idanagrafica, codice, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento='.prepare($idintervento).') AS data FROM `in_interventi` WHERE id='.prepare($idintervento));
            $idanagrafica = $rs[0]['idanagrafica'];
            $data = $rs[0]['data'];
            $codice = $rs[0]['codice'];

            //Fatturo le ore di lavoro raggruppate per costo orario
            $rst = $dbo->fetchArray('SELECT SUM( ROUND( TIMESTAMPDIFF( MINUTE, orario_inizio, orario_fine ) / 60, '.get_var('Cifre decimali per quantità').' ) ) AS tot_ore, SUM(prezzo_ore_consuntivo) AS tot_prezzo_ore_consuntivo, SUM(sconto) AS tot_sconto, prezzo_ore_unitario FROM in_interventi_tecnici WHERE idintervento='.prepare($idintervento).' GROUP BY prezzo_ore_unitario');
            
            //Aggiunta riga intervento sul documento
            if( sizeof($rst) == 0 ){
                $_SESSION['warnings'][] = tr('L\'intervento _NUM_ non ha sessioni di lavoro!', [
                    '_NUM_' => $idintervento,
                ]);
            } else {
                for( $i=0; $i<sizeof($rst); $i++ ){
                    $ore = $rst[$i]['tot_ore'];
                    
                    // Calcolo iva
                    $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
                    $rs = $dbo->fetchArray($query);

                    $sconto = $rst[$i]['tot_sconto'];
                    $subtot = $rst[$i]['tot_prezzo_ore_consuntivo'];
                    $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
                    $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
                    $desc_iva = $rs[0]['descrizione'];

                    // Calcolo rivalsa inps
                    $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare(get_var('Percentuale rivalsa INPS'));
                    $rs = $dbo->fetchArray($query);
                    $rivalsainps = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];

                    // Calcolo ritenuta d'acconto
                    $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare(get_var("Percentuale ritenuta d'acconto"));
                    $rs = $dbo->fetchArray($query);
                    $ritenutaacconto = ($subtot - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];
                    
                    $query = 'INSERT INTO co_righe_documenti(iddocumento, idintervento, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, `order`) VALUES('.prepare($id_record).', '.prepare($idintervento).', '.prepare($idconto).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto).", 'UNT', 'ore', ".prepare($ore).', '.prepare(get_var('Percentuale rivalsa INPS')).', '.prepare($rivalsainps).', '.prepare(get_var("Percentuale ritenuta d'acconto")).', '.prepare($ritenutaacconto).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';
                    $dbo->query($query);
                }
            }
            
            
            $costi_intervento = get_costi_intervento($idintervento);

            //Fatturo i diritti di chiamata raggruppati per costo
            $rst = $dbo->fetchArray('SELECT COUNT(id) AS qta, SUM(prezzo_dirittochiamata) AS tot_prezzo_dirittochiamata FROM in_interventi_tecnici WHERE idintervento='.prepare($idintervento).' AND prezzo_dirittochiamata > 0 GROUP BY prezzo_dirittochiamata');

            // Aggiunta diritto di chiamata se esiste
            for( $i=0; $i<sizeof($rst); $i++ ){
                // Calcolo iva
                $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
                $rs = $dbo->fetchArray($query);

                $iva = $rst[$i]['tot_prezzo_dirittochiamata'] / 100 * $rs[0]['percentuale'];
                $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
                $desc_iva = $rs[0]['descrizione'];

                // Calcolo rivalsa inps
                $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare(get_var('Percentuale rivalsa INPS'));
                $rs = $dbo->fetchArray($query);
                $rivalsainps = $rst[$i]['tot_prezzo_dirittochiamata'] / 100 * $rs[0]['percentuale'];

                // Calcolo ritenuta d'acconto
                $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare(get_var("Percentuale ritenuta d'acconto"));
                $rs = $dbo->fetchArray($query);
                $ritenutaacconto = $rst[$i]['tot_prezzo_dirittochiamata'] / 100 * $rs[0]['percentuale'];

                $query = 'INSERT INTO co_righe_documenti(
                            iddocumento,
                            idintervento,
                            idconto,
                            idiva,
                            desc_iva,
                            iva,
                            iva_indetraibile,
                            descrizione,
                            subtotale,
                            sconto,
                            sconto_unitario,
                            tipo_sconto,
                            um,
                            qta,
                            idrivalsainps,
                            rivalsainps,
                            idritenutaacconto,
                            ritenutaacconto,
                            `order`)
                        VALUES(
                            '.prepare($id_record).',
                            '.prepare($idintervento).',
                            '.prepare($idconto).',
                            '.prepare($idiva).',
                            '.prepare($desc_iva).',
                            '.prepare($iva).',
                            '.prepare($iva_indetraibile).',
                            "Diritto di chiamata",
                            '.prepare($rst[$i]['tot_prezzo_dirittochiamata']).',
                            0,
                            0,
                            "UNT",
                            "-",
							'.$rst[$i]['qta'].',
                            '.prepare(get_var('Percentuale rivalsa INPS')).',
                            '.prepare($rivalsainps).',
                            '.prepare(get_var("Percentuale ritenuta d'acconto")).',
                            '.prepare($ritenutaacconto).',
                            (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).')
                        )';
                $dbo->query($query);
            }

            // Collego in fattura eventuali articoli collegati all'intervento
            $rs2 = $dbo->fetchArray('SELECT mg_articoli_interventi.*, idarticolo FROM mg_articoli_interventi INNER JOIN mg_articoli ON mg_articoli_interventi.idarticolo=mg_articoli.id WHERE idintervento='.prepare($idintervento).' AND (idintervento NOT IN(SELECT idintervento FROM co_righe_preventivi WHERE idpreventivo IN(SELECT idpreventivo FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).')) AND idintervento NOT IN(SELECT idintervento FROM co_righe_contratti WHERE idcontratto IN(SELECT idcontratto FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).')) )');
            for ($i = 0; $i < sizeof($rs2); ++$i) {
                $riga = add_articolo_infattura($id_record, $rs2[$i]['idarticolo'], $rs2[$i]['descrizione'], $rs2[$i]['idiva'], $rs2[$i]['qta'], $rs2[$i]['prezzo_vendita'] * $rs2[$i]['qta'], $rs2[$i]['sconto'], $rs2[$i]['sconto_unitario'], $rs2[$i]['tipo_sconto'], $idintervento, 0, $rs2[$i]['um']);

                // Lettura lotto, serial, altro dalla riga dell'ordine
                $dbo->query('INSERT INTO mg_prodotti (id_riga_documento, id_articolo, dir, serial, lotto, altro) SELECT '.prepare($riga).', '.prepare($rs2[$i]['idarticolo']).', '.prepare($dir).', serial, lotto, altro FROM mg_prodotti AS t WHERE id_riga_intervento='.prepare($rs2[$i]['id']));
            }

            // Aggiunta spese aggiuntive come righe generiche
            $query = 'SELECT * FROM in_righe_interventi WHERE idintervento='.prepare($idintervento).' AND (idintervento NOT IN(SELECT idintervento FROM co_righe_preventivi WHERE idpreventivo IN(SELECT idpreventivo FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).')) AND idintervento NOT IN(SELECT idintervento FROM co_righe_contratti WHERE idcontratto IN(SELECT idcontratto FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).')) )';
            $rsr = $dbo->fetchArray($query);
            if (sizeof($rsr) > 0) {
                for ($i = 0; $i < sizeof($rsr); ++$i) {
                    // Calcolo iva
                    $query = 'SELECT * FROM co_iva WHERE id='.prepare($rsr[$i]['idiva']);
                    $rs = $dbo->fetchArray($query);
                    $desc_iva = $rs[0]['descrizione'];

                    $subtot = $rsr[$i]['prezzo_vendita'] * $rsr[$i]['qta'];
                    $sconto = $rsr[$i]['sconto'];
                    $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
                    $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];

                    // Calcolo rivalsa inps
                    $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare(get_var('Percentuale rivalsa INPS'));
                    $rs = $dbo->fetchArray($query);
                    $rivalsainps = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];

                    // Calcolo ritenuta d'acconto
                    $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare(get_var("Percentuale ritenuta d'acconto"));
                    $rs = $dbo->fetchArray($query);
                    $ritenutaacconto = ($subtot - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];

                    $query = 'INSERT INTO co_righe_documenti(iddocumento, idintervento, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, `order`) VALUES('.prepare($id_record).', '.prepare($idintervento).', '.prepare($idconto).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($rsr[$i]['descrizione']).', '.prepare($subtot).', '.prepare($rsr[$i]['sconto']).', '.prepare($rsr[$i]['sconto_unitario']).', '.prepare($rsr[$i]['tipo_sconto']).', '.prepare($rsr[$i]['um']).', '.prepare($rsr[$i]['qta']).', '.prepare(get_var('Percentuale rivalsa INPS')).', '.prepare($rivalsainps).', '.prepare(get_var("Percentuale ritenuta d'acconto")).', '.prepare($ritenutaacconto).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';
                    $dbo->query($query);
                }
            }

            // Aggiunta km come "Trasferta" (se c'è)
            if ($costi_intervento['viaggio_addebito'] > 0) {
                // Calcolo iva
                $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
                $dati = $dbo->fetchArray($query);
                $desc_iva = $dati[0]['descrizione'];

                $subtot = $costi_intervento['viaggio_addebito'];
                $sconto = $costi_intervento['viaggio_addebito'] - $costi_intervento['viaggio_scontato'];
                $iva = ($subtot - $sconto) / 100 * $dati[0]['percentuale'];
                $iva_indetraibile = $iva / 100 * $dati[0]['indetraibile'];

                // Calcolo rivalsa inps
                $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare(get_var('Percentuale rivalsa INPS'));
                $dati = $dbo->fetchArray($query);
                $rivalsainps = ($subtot - $sconto) / 100 * $dati[0]['percentuale'];

                // Calcolo ritenuta d'acconto
                $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare(get_var("Percentuale ritenuta d'acconto"));
                $dati = $dbo->fetchArray($query);
                $ritenutaacconto = ($subtot - $sconto + $rivalsainps) / 100 * $dati[0]['percentuale'];

                $query = 'INSERT INTO co_righe_documenti(iddocumento, idintervento, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, `order`) VALUES('.prepare($id_record).', '.prepare($idintervento).', '.prepare($idconto).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare('Trasferta intervento '.$codice.' del '.Translator::dateToLocale($data)).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto).", 'UNT', '', 1, ".prepare(get_var('Percentuale rivalsa INPS')).', '.prepare($rivalsainps).', '.prepare(get_var("Percentuale ritenuta d'acconto")).', '.prepare($ritenutaacconto).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';
                $dbo->query($query);
            }

            // Aggiunta sconto
            if (!empty($costi_intervento['sconto_globale'])) {
                $subtot = -$costi_intervento['sconto_globale'];

                // Calcolo iva
                $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
                $rs = $dbo->fetchArray($query);
                $desc_iva = $rs[0]['descrizione'];

                $iva = ($subtot) / 100 * $rs[0]['percentuale'];
                $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];

                // Calcolo rivalsa inps
                $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare(get_var('Percentuale rivalsa INPS'));
                $rs = $dbo->fetchArray($query);
                $rivalsainps = ($subtot) / 100 * $rs[0]['percentuale'];

                // Calcolo ritenuta d'acconto
                $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare(get_var("Percentuale ritenuta d'acconto"));
                $rs = $dbo->fetchArray($query);
                $ritenutaacconto = ($subtot + $rivalsainps) / 100 * $rs[0]['percentuale'];

                $query = 'INSERT INTO co_righe_documenti(iddocumento, idintervento, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, `order`) VALUES('.prepare($id_record).', NULL, '.prepare($idconto).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare('Sconto '.$descrizione).', '.prepare($subtot).', 1, '.prepare(get_var('Percentuale rivalsa INPS')).', '.prepare($rivalsainps).', '.prepare(get_var("Percentuale ritenuta d'acconto")).', '.prepare($ritenutaacconto).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';
                $dbo->query($query);
            }

            // Ricalcolo inps, ritenuta e bollo
            if ($dir == 'entrata') {
                ricalcola_costiagg_fattura($id_record);
            } else {
                ricalcola_costiagg_fattura($id_record, 0, 0, 0);
            }

            // Metto l'intervento in stato "Fatturato"
            $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Fatturato') WHERE id=".prepare($idintervento));

            $_SESSION['infos'][] = tr('Intervento _NUM_ aggiunto!', [
                '_NUM_' => $idintervento,
            ]);
            /*
            else {
            $_SESSION['errors'][] = tr("Errore durante l'inserimento dell'intervento _NUM_ in fattura!", [
                '_NUM_' => $idintervento,
            ]);
            }*/
        }
        break;

    case 'addpreventivo':
        if (!empty($id_record) && isset($post['idpreventivo'])) {
            $idpreventivo = post('idpreventivo');
            $descrizione = post('descrizione');
            $idiva = post('idiva');
            $idconto = post('idconto');

            $prezzo = $post['prezzo'];
            $qta = 1;

            // Calcolo dello sconto
            $sconto_unitario = $post['sconto'];
            $tipo_sconto = $post['tipo_sconto'];
            $sconto = ($tipo_sconto == 'PRC') ? ($prezzo * $sconto_unitario) / 100 : $sconto_unitario;
            $sconto = $sconto * $qta;

            $subtot = 0;
            $aggiorna_budget = ($post['aggiorna_budget'] == 'on') ? 1 : 0;

            // Leggo l'anagrafica del cliente
            $rs = $dbo->fetchArray('SELECT idanagrafica, numero FROM `co_preventivi` WHERE id='.prepare($idpreventivo));
            $idanagrafica = $rs[0]['idanagrafica'];
            $numero = $rs[0]['numero'];

            // Calcolo iva
            $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
            $rs = $dbo->fetchArray($query);
            $iva = $prezzo / 100 * $rs[0]['percentuale'];
            $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
            $desc_iva = $rs[0]['descrizione'];

            // Calcolo rivalsa inps
            $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare(get_var('Percentuale rivalsa INPS'));
            $rs = $dbo->fetchArray($query);
            $rivalsainps = ($prezzo - $sconto) / 100 * $rs[0]['percentuale'];

            // Calcolo ritenuta d'acconto
            $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare(get_var("Percentuale ritenuta d'acconto"));
            $rs = $dbo->fetchArray($query);
            $ritenutaacconto = ($prezzo - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];

            if (!empty($post['import'])) {
                // Replicazione delle righe del preventivo sul documento
                $righe = $dbo->fetchArray('SELECT idarticolo, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, um, qta, sconto, sconto_unitario, tipo_sconto FROM co_righe_preventivi WHERE idpreventivo='.prepare($idpreventivo));
                foreach ($righe as $key => $riga) {
                    $dbo->insert('co_righe_documenti', [
                        'iddocumento' => $id_record,
                        'idpreventivo' => $idpreventivo,
                        'idconto' => $idconto,
                        'idarticolo' => $riga['idarticolo'],
                        'idiva' => $riga['idiva'],
                        'desc_iva' => $riga['desc_iva'],
                        'iva' => $riga['iva'],
                        'iva_indetraibile' => $riga['iva_indetraibile'],
                        'descrizione' => str_replace('SCONTO', 'SCONTO '.$descrizione, $riga['descrizione']),
                        'subtotale' => $riga['subtotale'],
                        'um' => $riga['um'],
                        'qta' => $riga['qta'],
                        'sconto' => $riga['sconto'],
                        'sconto_unitario' => $riga['sconto_unitario'],
                        '#order' => '(SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).')',
                        'idritenutaacconto' => get_var("Percentuale ritenuta d'acconto"),
                        'ritenutaacconto' => $ritenutaacconto,
                        'idrivalsainps' => get_var('Percentuale rivalsa INPS'),
                        'rivalsainps' => $rivalsainps,
                    ]);

                    if (!empty($riga['idarticolo'])) {
                        add_movimento_magazzino($riga['idarticolo'], -$riga['qta'], ['iddocumento' => $id_record]);
                    }
                }
            } else {
                // Aggiunta riga preventivo sul documento
                $query = 'INSERT INTO co_righe_documenti(iddocumento, idpreventivo, idconto, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idritenutaacconto, ritenutaacconto, idrivalsainps, rivalsainps, `order`) VALUES('.prepare($id_record).', '.prepare($idpreventivo).', '.prepare($idconto).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($prezzo).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).", '-', 1, ".prepare(get_var("Percentuale ritenuta d'acconto")).', '.prepare($ritenutaacconto).', '.prepare(get_var('Percentuale rivalsa INPS')).', '.prepare($rivalsainps).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';
                $dbo->query($query);

                // Aggiorno lo stato degli interventi collegati al preventivo se ce ne sono
                $query2 = 'SELECT idpreventivo FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND NOT idpreventivo=0 AND idpreventivo IS NOT NULL';
                $rs2 = $dbo->fetchArray($query2);
                for ($j = 0; $j < sizeof($rs2); ++$j) {
                    $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Fatturato') WHERE id IN (SELECT idintervento FROM co_preventivi_interventi WHERE idpreventivo=".prepare($rs2[$j]['idpreventivo']).')');
                }
            }

            $_SESSION['infos'][] = tr('Preventivo _NUM_ aggiunto!', [
                '_NUM_' => $numero,
            ]);

            // Aggiorno il budget sul preventivo con l'importo inserito in fattura e imposto lo stato del preventivo "In attesa di pagamento" (se selezionato)
            if ($aggiorna_budget) {
                $dbo->query('UPDATE co_preventivi SET budget='.prepare($prezzo).' WHERE id='.prepare($idpreventivo));
            }
            $dbo->query("UPDATE co_preventivi SET idstato=(SELECT id FROM co_statipreventivi WHERE descrizione='In attesa di pagamento') WHERE id=".prepare($idpreventivo));

            // Ricalcolo inps, ritenuta e bollo
            if ($dir == 'entrata') {
                ricalcola_costiagg_fattura($id_record);
            } else {
                ricalcola_costiagg_fattura($id_record, 0, 0, 0);
            }
        }

        break;

    case 'addcontratto':
        if (!empty($id_record) && isset($post['idcontratto'])) {
            $idcontratto = post('idcontratto');
            $descrizione = post('descrizione');
            $idiva = post('idiva');
            $idconto = post('idconto');

            $prezzo = $post['prezzo'];
            $qta = 1;

            // Calcolo dello sconto
            $sconto_unitario = $post['sconto'];
            $tipo_sconto = $post['tipo_sconto'];
            $sconto = ($tipo_sconto == 'PRC') ? ($prezzo * $sconto_unitario) / 100 : $sconto_unitario;
            $sconto = $sconto * $qta;

            $subtot = 0;
            $aggiorna_budget = ($post['aggiorna_budget'] == 'on') ? 1 : 0;

            // Leggo l'anagrafica del cliente
            $rs = $dbo->fetchArray('SELECT idanagrafica, numero FROM `co_contratti` WHERE id='.prepare($idcontratto));
            $idanagrafica = $rs[0]['idanagrafica'];
            $numero = $rs[0]['numero'];

            // Calcolo iva
            $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
            $rs = $dbo->fetchArray($query);
            $iva = $prezzo / 100 * $rs[0]['percentuale'];
            $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
            $desc_iva = $rs[0]['descrizione'];

            // Calcolo rivalsa inps
            $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare(get_var('Percentuale rivalsa INPS'));
            $rs = $dbo->fetchArray($query);
            $rivalsainps = ($prezzo - $sconto) / 100 * $rs[0]['percentuale'];

            // Calcolo ritenuta d'acconto
            $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare(get_var("Percentuale ritenuta d'acconto"));
            $rs = $dbo->fetchArray($query);
            $ritenutaacconto = ($prezzo - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];

            // Aggiunta riga contratto sul documento
            $query = 'INSERT INTO co_righe_documenti(iddocumento, idcontratto, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, `order`) VALUES('.prepare($id_record).', '.prepare($idcontratto).', '.prepare($idconto).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($prezzo).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).", '-', 1, ".prepare(get_var('Percentuale rivalsa INPS')).', '.prepare($rivalsainps).', '.prepare(get_var("Percentuale ritenuta d'acconto")).', '.prepare($ritenutaacconto).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';
            if ($dbo->query($query)) {
                $_SESSION['infos'][] = tr('Contratto _NUM_ aggiunto!', [
                    '_NUM_' => $numero,
                ]);

                // Aggiorno il budget sul contratto con l'importo inserito in fattura e imposto lo stato del contratto "In attesa di pagamento" (se selezionato)
                if ($aggiorna_budget) {
                    $dbo->query('UPDATE co_contratti SET budget='.prepare($prezzo).' WHERE id='.prepare($idcontratto));
                }

                $dbo->query("UPDATE co_contratti SET idstato=(SELECT id FROM co_staticontratti WHERE descrizione='In attesa di pagamento') WHERE id=".prepare($idcontratto));

                // Ricalcolo inps, ritenuta e bollo
                if ($dir == 'entrata') {
                    ricalcola_costiagg_fattura($id_record);
                } else {
                    ricalcola_costiagg_fattura($id_record, 0, 0, 0);
                }
            }
        }
        break;

    case 'addarticolo':
        if (!empty($id_record) && isset($post['idarticolo'])) {
            $idarticolo = post('idarticolo');
            $descrizione = post('descrizione');

            $idiva = post('idiva');
            $idconto = post('idconto');
            $idum = post('um');

            $qta = $post['qta'];
            $prezzo = $post['prezzo'];

            // Calcolo dello sconto
            $sconto_unitario = $post['sconto'];
            $tipo_sconto = $post['tipo_sconto'];
            $sconto = ($tipo_sconto == 'PRC') ? ($prezzo * $sconto_unitario) / 100 : $sconto_unitario;
            $sconto = $sconto * $qta;

            add_articolo_infattura($id_record, $idarticolo, $descrizione, $idiva, $qta, $prezzo * $qta, $sconto, $sconto_unitario, $tipo_sconto, '0', $idconto, $idum);

            $_SESSION['infos'][] = tr('Articolo aggiunto!');
        }
        break;

    case 'addriga':
        if (!empty($id_record)) {
            // Selezione costi da intervento
            $descrizione = post('descrizione');
            $idiva = post('idiva');
            $idconto = post('idconto');
            $um = post('um');

            $qta = $post['qta'];
            $prezzo = $post['prezzo'];

            // Calcolo dello sconto
            $sconto_unitario = $post['sconto'];
            $tipo_sconto = $post['tipo_sconto'];
            $sconto = ($tipo_sconto == 'PRC') ? ($prezzo * $sconto_unitario) / 100 : $sconto_unitario;
            $sconto = $sconto * $qta;

            $subtot = $prezzo * $qta;

            // Calcolo iva
            $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
            $rs = $dbo->fetchArray($query);
            $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
            $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
            $desc_iva = $rs[0]['descrizione'];

            // Calcolo rivalsa inps
            $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare(post('idrivalsainps'));
            $rs = $dbo->fetchArray($query);
            $rivalsainps = ($prezzo * $qta - $sconto) / 100 * $rs[0]['percentuale'];

            // Calcolo ritenuta d'acconto
            $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare(post('idritenutaacconto'));
            $rs = $dbo->fetchArray($query);
            $ritenutaacconto = (($prezzo * $qta) + $rivalsainps) / 100 * $rs[0]['percentuale'];

            // Aggiunta riga generica sul documento
            $query = 'INSERT INTO co_righe_documenti(iddocumento, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, `order`) VALUES('.prepare($id_record).', '.prepare($idconto).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', '.prepare(post('idrivalsainps')).', '.prepare($rivalsainps).', '.prepare(post('idritenutaacconto')).', '.prepare($ritenutaacconto).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';

            if ($dbo->query($query)) {
                $_SESSION['infos'][] = tr('Riga aggiunta!');

                // Ricalcolo inps, ritenuta e bollo
                if ($dir == 'entrata') {
                    ricalcola_costiagg_fattura($id_record);
                } else {
                    ricalcola_costiagg_fattura($id_record);
                }
            }
        }
        break;

    case 'adddescrizione':
        if (!empty($id_record)) {
            $descrizione = post('descrizione');
            $query = 'INSERT INTO co_righe_documenti(iddocumento, descrizione, is_descrizione) VALUES('.prepare($id_record).', '.prepare($descrizione).', 1)';

            if ($dbo->query($query)) {
                $_SESSION['infos'][] = tr('Riga descrittiva aggiunta!');
            }
        }
        break;

    case 'editriga':
        if (isset($post['idriga'])) {
            // Selezione costi da intervento
            $idriga = post('idriga');
            $descrizione = post('descrizione');
            $idiva = post('idiva');
            $idconto = post('idconto');
            $um = post('um');

            $qta = $post['qta'];
            $prezzo = $post['prezzo'];

            // Calcolo dello sconto
            $sconto_unitario = $post['sconto'];
            $tipo_sconto = $post['tipo_sconto'];
            $sconto = ($tipo_sconto == 'PRC') ? ($prezzo * $sconto_unitario) / 100 : $sconto_unitario;
            $sconto = $sconto * $qta;

            $subtot = $prezzo * $qta;

            // Lettura idarticolo dalla riga documento
            $rs = $dbo->fetchArray('SELECT * FROM co_righe_documenti WHERE id='.prepare($idriga));
            $idarticolo = $rs[0]['idarticolo'];
            $idddt = $rs[0]['idddt'];
            $idordine = $rs[0]['idordine'];
            $old_qta = $rs[0]['qta'];
            $iddocumento = $rs[0]['iddocumento'];
            $abilita_serial = $rs[0]['abilita_serial'];
            $is_descrizione = $rs[0]['is_descrizione'];

            // Controllo per gestire i serial
            if (!empty($idarticolo)) {
                if (!controlla_seriali('id_riga_documento', $idriga, $old_qta, $qta, $dir)) {
                    $_SESSION['errors'][] = tr('Alcuni serial number sono già stati utilizzati!');

                    return;
                }
            }

            // Se c'è un collegamento ad un ddt, aggiorno la quantità evasa
            if (!empty($idddt)) {
                $dbo->query('UPDATE dt_righe_ddt SET qta_evasa=qta_evasa-'.$old_qta.' + '.$qta.' WHERE descrizione='.prepare($rs[0]['descrizione']).' AND idarticolo='.prepare($rs[0]['idarticolo']).' AND idddt='.prepare($idddt).' AND idiva='.prepare($rs[0]['idiva']));
            }

            // Se c'è un collegamento ad un ordine, aggiorno la quantità evasa
            if (!empty($idddt)) {
                $dbo->query('UPDATE or_righe_ordini SET qta_evasa=qta_evasa-'.$old_qta.' + '.$qta.' WHERE descrizione='.prepare($rs[0]['descrizione']).' AND idarticolo='.prepare($rs[0]['idarticolo']).' AND idordine='.prepare($idordine).' AND idiva='.prepare($rs[0]['idiva']));
            }

            // Calcolo iva
            $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
            $rs = $dbo->fetchArray($query);
            $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
            $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
            $desc_iva = $rs[0]['descrizione'];

            // Calcolo rivalsa inps
            $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare(post('idrivalsainps'));
            $rs = $dbo->fetchArray($query);
            $rivalsainps = ($prezzo * $qta - $sconto) / 100 * $rs[0]['percentuale'];

            // Calcolo ritenuta d'acconto
            $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare(post('idritenutaacconto'));
            $rs = $dbo->fetchArray($query);
            $ritenutaacconto = (($prezzo * $qta) - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];

            if ($is_descrizione == 0) {
                // Modifica riga generica sul documento
                $query = 'UPDATE co_righe_documenti SET idconto='.prepare($idconto).', idiva='.prepare($idiva).', desc_iva='.prepare($desc_iva).', iva='.prepare($iva).', iva_indetraibile='.prepare($iva_indetraibile).', descrizione='.prepare($descrizione).', subtotale='.prepare($subtot).', sconto='.prepare($sconto).', sconto_unitario='.prepare($sconto_unitario).', tipo_sconto='.prepare($tipo_sconto).', um='.prepare($um).', idritenutaacconto='.prepare(post('idritenutaacconto')).', ritenutaacconto='.prepare($ritenutaacconto).', idrivalsainps='.prepare(post('idrivalsainps')).', rivalsainps='.prepare($rivalsainps).', qta='.prepare($qta).' WHERE id='.prepare($idriga).' AND iddocumento='.prepare($iddocumento);
            } else {
                // Modifica riga descrizione sul documento
                $query = 'UPDATE co_righe_documenti SET descrizione='.prepare($descrizione).' WHERE id='.prepare($idriga).' AND iddocumento='.prepare($iddocumento);
            }
            if ($dbo->query($query)) {
                // Modifica per gestire i serial
                if (!empty($idarticolo)) {
                    $new_qta = $qta - $old_qta;
                    $new_qta = ($dir == 'entrata') ? -$new_qta : $new_qta;
                    add_movimento_magazzino($idarticolo, $new_qta, ['iddocumento' => $id_record]);
                }

                $_SESSION['infos'][] = tr('Riga modificata!');

                // Ricalcolo inps, ritenuta e bollo
                if ($dir == 'entrata') {
                    ricalcola_costiagg_fattura($id_record);
                } else {
                    ricalcola_costiagg_fattura($id_record);
                }
            }
        }
        break;

    // Creazione fattura da ddt
    case 'fattura_da_ddt':
        $totale_fattura = 0.00;
        $data = $post['data'];
        $idanagrafica = $post['idanagrafica'];
        $idarticolo = $post['idarticolo'];
        $idpagamento = $post['idpagamento'];
        $idddt = $post['idddt'];
        $numero = get_new_numerofattura($data);

        if ($dir == 'entrata') {
            $numero_esterno = get_new_numerosecondariofattura($data);
        } else {
            $numero_esterno = '';
        }

        if ($dir == 'entrata') {
            $tipo_documento = 'Fattura differita di vendita';
            $idconto = get_var('Conto predefinito fatture di vendita');
        } else {
            $tipo_documento = 'Fattura differita di acquisto';
            $idconto = get_var('Conto predefinito fatture di acquisto');
        }

        // Creazione nuova fattura
        $dbo->query('INSERT INTO co_documenti(numero, numero_esterno, data, idanagrafica, idtipodocumento, idstatodocumento, idpagamento, idconto) VALUES('.prepare($numero).', '.prepare($numero_esterno).', '.prepare($data).', '.prepare($idanagrafica).', (SELECT id FROM co_tipidocumento WHERE descrizione='.prepare($tipo_documento)."), (SELECT id FROM co_statidocumento WHERE descrizione='Bozza'), ".prepare($idpagamento).', '.prepare($idconto).')');
        $id_record = $dbo->lastInsertedID();

        // Lettura di tutte le righe della tabella in arrivo
        foreach ($post['qta_da_evadere'] as $i => $value) {
            // Processo solo le righe da evadere
            if ($post['evadere'][$i] == 'on') {
                $idrigaddt = $i;
                $idarticolo = post('idarticolo')[$i];
                $descrizione = post('descrizione')[$i];
                $qta = $post['qta_da_evadere'][$i];
                $um = $post['um'][$i];
                $subtot = $post['subtot'][$i] * $qta;
                $sconto = $post['sconto'][$i];
                $sconto = $sconto * $qta;
                $idiva = post('idiva')[$i];

                $qprc = 'SELECT tipo_sconto, sconto_unitario FROM dt_righe_ddt WHERE id='.prepare($idrigaddt);
                $rsprc = $dbo->fetchArray($qprc);

                $sconto_unitario = $rsprc[0]['sconto_unitario'];
                $tipo_sconto = $rsprc[0]['tipo_sconto'];

                // Leggo la descrizione iva
                $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
                $rs = $dbo->fetchArray($query);
                $perc_iva = $rs[0]['percentuale'];
                $desc_iva = $rs[0]['descrizione'];
                $iva = ($subtot - $sconto) / 100 * $perc_iva;

                // Calcolo l'iva indetraibile
                $q = 'SELECT indetraibile FROM co_iva WHERE id='.prepare($idiva);
                $rs = $dbo->fetchArray($q);
                $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];

                // Se sto aggiungendo un articolo uso la funzione per inserirlo e incrementare la giacenza
                if (!empty($idarticolo)) {
                    $idiva_acquisto = $idiva;
                    $prezzo_acquisto = $subtot;
                    $idriga = add_articolo_infattura($id_record, $idarticolo, $descrizione, $idiva_acquisto, $qta, $prezzo_acquisto, $sconto, $sconto_unitario, $tipo_sconto);

                    // Aggiornamento seriali dalla riga dell'ordine
                    $serials = is_array($post['serial'][$i]) ? $post['serial'][$i] : [];
                    $serials = array_filter($serials, function ($value) { return !empty($value); });

                    $dbo->sync('mg_prodotti', ['id_riga_documento' => $idriga, 'dir' => $dir, 'id_articolo' => $idarticolo], ['serial' => $serials]);
                }

                // Inserimento riga normale
                elseif ($qta != 0) {
                    $query = 'INSERT INTO co_righe_documenti(iddocumento, idarticolo, descrizione, idddt, idiva, desc_iva, iva, iva_indetraibile, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, `order`) VALUES('.prepare($id_record).', '.prepare($idarticolo).', '.prepare($descrizione).', '.prepare($idddt).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';

                    $dbo->query($query);
                }

                // Scalo la quantità dal ddt
                $dbo->query('UPDATE dt_righe_ddt SET qta_evasa = qta_evasa+'.$qta.' WHERE id='.prepare($idrigaddt));
            }
        }

        ricalcola_costiagg_fattura($id_record);

        $_SESSION['infos'][] = tr('Creata una nuova fattura!');

        break;

    // Creazione fattura da ordine
    case 'fattura_da_ordine':
        $totale_fattura = 0.00;
        $data = $post['data'];
        $idanagrafica = $post['idanagrafica'];
        $idarticolo = $post['idarticolo'];
        $idpagamento = $post['idpagamento'];
        $idconto = $post['idconto'];
        $idordine = $post['idordine'];
        $numero = get_new_numerofattura($data);
        $numero_esterno = get_new_numerosecondariofattura($data);

        $tipo_documento = ($dir == 'entrata') ? 'Fattura immediata di vendita' : 'Fattura immediata di acquisto';

        // Creazione nuova fattura
        $dbo->query('INSERT INTO co_documenti(numero, numero_esterno, data, idanagrafica, idtipodocumento, idstatodocumento, idpagamento, idconto) VALUES('.prepare($numero).', '.prepare($numero_esterno).', '.prepare($data).', '.prepare($idanagrafica).', (SELECT id FROM co_tipidocumento WHERE descrizione='.prepare($tipo_documento)."), (SELECT id FROM co_statidocumento WHERE descrizione='Bozza'), ".prepare($idpagamento).', '.prepare($idconto).')');
        $id_record = $dbo->lastInsertedID();

        // Lettura di tutte le righe della tabella in arrivo
        foreach ($post['qta_da_evadere'] as $i => $value) {
            // Processo solo le righe da evadere
            if ($post['evadere'][$i] == 'on') {
                $idrigaordine = $i;
                $idarticolo = post('idarticolo')[$i];
                $descrizione = post('descrizione')[$i];
                $qta = post('qta_da_evadere')[$i];
                $um = post('um')[$i];
                $subtot = save($post['subtot'][$i] * $qta);
                $idiva = post('idiva')[$i];
                $iva = save($post['iva'][$i] * $qta);
                $sconto = post('sconto')[$i];
                $sconto = $sconto * $qta;

                $qprc = 'SELECT tipo_sconto, sconto_unitario FROM or_righe_ordini WHERE id='.prepare($idrigaordine);
                $rsprc = $dbo->fetchArray($qprc);

                $sconto_unitario = $rsprc[0]['sconto_unitario'];
                $tipo_sconto = $rsprc[0]['tipo_sconto'];

                // Calcolo l'iva indetraibile
                $q = 'SELECT indetraibile FROM co_iva WHERE id='.prepare($idiva);
                $rs = $dbo->fetchArray($q);
                $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];

                // Leggo la descrizione iva
                $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
                $rs = $dbo->fetchArray($query);
                $desc_iva = $rs[0]['descrizione'];

                // Se sto aggiungendo un articolo uso la funzione per inserirlo e incrementare la giacenza
                if (!empty($idarticolo)) {
                    $idiva_acquisto = $idiva;
                    $prezzo_acquisto = $subtot;
                    $idriga = add_articolo_infattura($id_record, $idarticolo, $descrizione, $idiva_acquisto, $qta, $prezzo_acquisto, $sconto, $sconto_unitario, $tipo_sconto);

                    // Aggiornamento seriali dalla riga dell'ordine
                    $serials = is_array($post['serial'][$i]) ? $post['serial'][$i] : [];
                    $serials = array_filter($serials, function ($value) { return !empty($value); });

                    $dbo->sync('mg_prodotti', ['id_riga_documento' => $idriga, 'dir' => $dir, 'id_articolo' => $idarticolo], ['serial' => $serials]);

                    // Imposto la provenienza dell'ordine
                    $dbo->query('UPDATE co_righe_documenti SET idordine='.prepare($idordine).' WHERE id='.prepare($idriga));
                }

                // Inserimento riga normale
                elseif ($qta != 0) {
                    $dbo->query('INSERT INTO co_righe_documenti(iddocumento, idarticolo, idordine, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, `order`) VALUES('.prepare($id_record).', '.prepare($idarticolo).', '.prepare($idordine).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))');
                    $idriga = $dbo->lastInsertedID();
                }

                // Scalo la quantità dall'ordine
                $dbo->query('UPDATE or_righe_ordini SET qta_evasa = qta_evasa+'.$qta.' WHERE id='.prepare($idrigaordine));
            }
        }

        ricalcola_costiagg_fattura($id_record);
        $_SESSION['infos'][] = tr('Creata una nuova fattura!');

        break;

    // aggiungi righe da ddt
    case 'add_ddt':
        $idddt = $post['iddocumento'];

        $rs = $dbo->fetchArray('SELECT * FROM co_documenti WHERE id='.prepare($id_record));
        $idconto = $rs[0]['idconto'];

        // Lettura di tutte le righe della tabella in arrivo
        foreach ($post['qta_da_evadere'] as $i => $value) {
            // Processo solo le righe da evadere
            if ($post['evadere'][$i] == 'on') {
                $idrigaddt = $i;
                $idarticolo = post('idarticolo')[$i];
                $descrizione = post('descrizione')[$i];

                $qta = $post['qta_da_evadere'][$i];
                $um = post('um')[$i];

                $subtot = $post['subtot'][$i] * $qta;
                $sconto = $post['sconto'][$i];
                $sconto = $sconto * $qta;

                $qprc = 'SELECT tipo_sconto, sconto_unitario FROM dt_righe_ddt WHERE id='.prepare($idrigaddt);
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
                    $riga = add_articolo_infattura($id_record, $idarticolo, $descrizione, $idiva_acquisto, $qta, $prezzo_acquisto, 0, 0, 'UNT', 0, $idconto);

                    // Lettura lotto, serial, altro dalla riga dell'ddt
                    $dbo->query('INSERT INTO mg_prodotti (id_riga_documento, id_articolo, dir, serial, lotto, altro) SELECT '.prepare($riga).', '.prepare($idarticolo).', '.prepare($dir).', serial, lotto, altro FROM mg_prodotti AS t WHERE id_riga_ddt='.prepare($idrigaddt));
                }

                // Inserimento riga normale
                elseif ($qta != 0) {
                    $query = 'INSERT INTO co_righe_documenti(iddocumento, idarticolo, descrizione, idconto, idddt, idiva, desc_iva, iva, iva_indetraibile, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, `order`) VALUES('.prepare($id_record).', '.prepare($idarticolo).', '.prepare($descrizione).', '.prepare($idconto).', '.prepare($idddt).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';
                    $dbo->query($query);
                }

                // Scalo la quantità dal ddt
                $dbo->query('UPDATE dt_righe_ddt SET qta_evasa = qta_evasa+'.$qta.' WHERE id='.prepare($idrigaddt));
            }
        }

        ricalcola_costiagg_fattura($id_record);

        $_SESSION['infos'][] = tr('Aggiunti nuovi articoli in fattura!');

        break;

    // Scollegamento intervento da documento
    case 'unlink_intervento':
        if (!empty($id_record) && isset($post['idriga'])) {
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
                ricalcola_costiagg_fattura($id_record, 0, 0, 0);
            }

            // Lettura interventi collegati
            //$query = 'SELECT id, idintervento FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idintervento IS NOT NULL';
            //$rs = $dbo->fetchArray($query);

            // Se ci sono degli interventi collegati li rimetto nello stato "Completato"
            //for ($i = 0; $i < sizeof($rs); ++$i) {
			$dbo->query("UPDATE in_interventi SET idstatointervento='OK' WHERE id=".prepare($idintervento));

			// Rimuovo dalla fattura gli articoli collegati all'intervento
			$rs2 = $dbo->fetchArray('SELECT idarticolo FROM mg_articoli_interventi WHERE idintervento='.prepare($idintervento));
			for ($j = 0; $j < sizeof($rs2); ++$j) {
				rimuovi_articolo_dafattura($rs[0]['idarticolo'], $id_record, $rs[0]['idrigadocumento']);
			}
            //}
			
			//rimuovo riga da co_righe_documenti
			$query = 'DELETE FROM `co_righe_documenti` WHERE iddocumento='.prepare($id_record).' AND id='.prepare($idriga);
            $dbo->query($query);

            $_SESSION['infos'][] = tr('Intervento _NUM_ rimosso!', [
                '_NUM_' => $idintervento,
            ]);
        }
        break;

    // Scollegamento articolo da documento
    case 'unlink_articolo':
        if (!empty($id_record) && isset($post['idarticolo'])) {
            $idriga = post('idriga');
            $idarticolo = post('idarticolo');

            if (!rimuovi_articolo_dafattura($idarticolo, $id_record, $idriga)) {
                $_SESSION['errors'][] = tr('Alcuni serial number sono già stati utilizzati!');

                return;
            }

            if ($dbo->query('DELETE FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND id='.prepare($idriga))) {
                // Ricalcolo inps, ritenuta e bollo
                if ($dir == 'entrata') {
                    ricalcola_costiagg_fattura($id_record);
                } else {
                    ricalcola_costiagg_fattura($id_record, 0, 0, 0);
                }

                $_SESSION['infos'][] = tr('Articolo rimosso!');
            }
        }
        break;

    // Scollegamento preventivo da documento
    case 'unlink_preventivo':
        if (isset($post['idriga'])) {
            $idriga = post('idriga');

            // Lettura preventivi collegati
            $query = 'SELECT iddocumento, idpreventivo FROM co_righe_documenti WHERE id='.prepare($idriga);
            $rsp = $dbo->fetchArray($query);
            $id_record = $rsp[0]['iddocumento'];
            $idpreventivo = $rsp[0]['idpreventivo'];

            $query = 'DELETE FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND id='.prepare($idriga);

            if ($dbo->query($query)) {
                // Se ci sono dei preventivi collegati li rimetto nello stato "In attesa di pagamento"
                for ($i = 0; $i < sizeof($rsp); ++$i) {
                    $dbo->query("UPDATE co_preventivi SET idstato=(SELECT id FROM co_statipreventivi WHERE descrizione='In lavorazione') WHERE id=".prepare($rsp[$i]['idpreventivo']));

                    // Aggiorno anche lo stato degli interventi collegati ai preventivi
                    $dbo->query("UPDATE in_interventi SET idstatointervento='OK' WHERE id IN (SELECT idintervento FROM co_preventivi_interventi WHERE idpreventivo=".prepare($rsp[$i]['idpreventivo']).')');
                }

                /*
                    Rimuovo tutti gli articoli dalla fattura collegati agli interventi che sono collegati a questo preventivo
                */
                $rs2 = $dbo->fetchArray('SELECT idintervento FROM co_preventivi_interventi WHERE idpreventivo='.prepare($idpreventivo)." AND NOT idpreventivo=''");
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
                    ricalcola_costiagg_fattura($id_record, 0, 0, 0);
                }

                $_SESSION['infos'][] = tr('Preventivo rimosso!');
            }
        }
        break;

    // Scollegamento contratto da documento
    case 'unlink_contratto':
        if (isset($post['idriga'])) {
            $idriga = post('idriga');

            // Lettura contratti collegati
            $query = 'SELECT iddocumento, idcontratto FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idcontratto IS NOT NULL AND NOT idcontratto=0';
            $rsp = $dbo->fetchArray($query);
            $id_record = $rsp[0]['iddocumento'];
            $idcontratto = $rsp[0]['idcontratto'];

            $query = 'DELETE FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idcontratto='.prepare($idcontratto);

            if ($dbo->query($query)) {
                // Se ci sono dei preventivi collegati li rimetto nello stato "In attesa di pagamento"
                for ($i = 0; $i < sizeof($rsp); ++$i) {
                    $dbo->query("UPDATE co_contratti SET idstato=(SELECT id FROM co_staticontratti WHERE descrizione='In lavorazione') WHERE id=".prepare($rsp[$i]['idcontratto']));

                    // Aggiorno anche lo stato degli interventi collegati ai contratti
                    $dbo->query("UPDATE in_interventi SET idstatointervento='OK' WHERE id IN (SELECT idintervento FROM co_righe_contratti WHERE idcontratto=".prepare($rsp[$i]['idcontratto']).')');
                }

                /*
                    Rimuovo tutti gli articoli dalla fattura collegati agli interventi che sono collegati a questo contratto
                */
                $rs2 = $dbo->fetchArray('SELECT idintervento FROM co_righe_contratti WHERE idcontratto='.prepare($idcontratto)." AND NOT idcontratto=''");
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
                    ricalcola_costiagg_fattura($id_record, 0, 0, 0);
                }

                $_SESSION['infos'][] = tr('Contratto rimosso!');
            }
        }
        break;

    // Scollegamento riga generica da documento
    case 'unlink_riga':
        if (isset($post['idriga'])) {
            $idriga = post('idriga');

            // Se la riga è stata creata da un ordine, devo riportare la quantità evasa nella tabella degli ordini
            // al valore di prima, riaggiungendo la quantità che sto togliendo
            $rs = $dbo->fetchArray('SELECT qta, descrizione, idarticolo, idordine, idiva FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND id='.prepare($idriga));

            // Rimpiazzo la quantità negli ordini
            $dbo->query('UPDATE or_righe_ordini SET qta_evasa=qta_evasa-'.$rs[0]['qta'].' WHERE descrizione='.prepare($rs[0]['descrizione']).' AND idarticolo='.prepare($rs[0]['idarticolo']).' AND idordine='.prepare($rs[0]['idordine']).' AND idiva='.prepare($rs[0]['idiva']));

            // Se la riga è stata creata da un ddt, devo riportare la quantità evasa nella tabella dei ddt
            // al valore di prima, riaggiungendo la quantità che sto togliendo
            $rs = $dbo->fetchArray('SELECT qta, descrizione, idarticolo, idddt, idiva FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND id='.prepare($idriga));

            // Rimpiazzo la quantità nei ddt
            $dbo->query('UPDATE dt_righe_ddt SET qta_evasa=qta_evasa-'.$rs[0]['qta'].' WHERE descrizione='.prepare($rs[0]['descrizione']).' AND idarticolo='.prepare($rs[0]['idarticolo']).' AND idddt='.prepare($rs[0]['idddt']).' AND idiva='.prepare($rs[0]['idiva']));

            $query = 'DELETE FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND id='.prepare($idriga);

            if ($dbo->query($query)) {
                // Ricalcolo inps, ritenuta e bollo
                if ($dir == 'entrata') {
                    ricalcola_costiagg_fattura($id_record);
                } else {
                    ricalcola_costiagg_fattura($id_record, 0, 0, 0);
                }

                $_SESSION['infos'][] = tr('Riga rimossa!');
            }
        }
        break;

    case 'add_serial':
        $idriga = $post['idriga'];
        $idarticolo = $post['idarticolo'];

        $serials = (array) $post['serial'];
        foreach ($serials as $key => $value) {
            if (empty($value)) {
                unset($serials[$key]);
            }
        }

        $dbo->sync('mg_prodotti', ['id_riga_documento' => $idriga, 'dir' => $dir, 'id_articolo' => $idarticolo], ['serial' => $serials]);

        break;

    case 'update_position':
        $start = filter('start');
        $end = filter('end');
        $id = filter('id');

        if ($start > $end) {
            $dbo->query('UPDATE `co_righe_documenti` SET `order`=`order` + 1 WHERE `order`>='.prepare($end).' AND `order`<'.prepare($start).' AND `iddocumento`='.prepare($id_record));
            $dbo->query('UPDATE `co_righe_documenti` SET `order`='.prepare($end).' WHERE id='.prepare($id));
        } elseif ($end != $start) {
            $dbo->query('UPDATE `co_righe_documenti` SET `order`=`order` - 1 WHERE `order`>'.prepare($start).' AND `order`<='.prepare($end).' AND `iddocumento`='.prepare($id_record));
            $dbo->query('UPDATE `co_righe_documenti` SET `order`='.prepare($end).' WHERE id='.prepare($id));
        }

        break;
}

// Aggiornamento stato dei ddt presenti in questa fattura in base alle quantità totali evase
if (!empty($id_record)) {
    $rs = $dbo->fetchArray('SELECT idddt FROM co_righe_documenti WHERE iddocumento='.prepare($id_record));

    for ($i = 0; $i < sizeof($rs); ++$i) {
        $dbo->query('UPDATE dt_ddt SET idstatoddt=(SELECT id FROM dt_statiddt WHERE descrizione="'.get_stato_ddt($rs[$i]['idddt']).'") WHERE id = '.prepare($rs[$i]['idddt']));
    }
}

// Aggiornamento stato degli ordini presenti in questa fattura in base alle quantità totali evase
if (!empty($id_record)) {
    $rs = $dbo->fetchArray('SELECT idordine FROM co_righe_documenti WHERE iddocumento='.prepare($id_record));

    for ($i = 0; $i < sizeof($rs); ++$i) {
        $dbo->query('UPDATE or_ordini SET idstatoordine=(SELECT id FROM or_statiordine WHERE descrizione="'.get_stato_ordine($rs[$i]['idordine']).'") WHERE id = '.prepare($rs[$i]['idordine']));
    }
}

// Aggiornamento sconto sulle righe
if (post('op') !== null && post('op') != 'update') {
    aggiorna_sconto([
        'parent' => 'co_documenti',
        'row' => 'co_righe_documenti',
    ], [
        'parent' => 'id',
        'row' => 'iddocumento',
    ], $id_record);
}
