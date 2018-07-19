<?php

include_once __DIR__.'/../../core.php';

include_once Modules::filepath('Fatture di vendita', 'modutil.php');

switch (post('op')) {
    case 'add':
        $all_ok = true;
        $iddocumento = post('iddocumento');
        $data = post('data');
        $idmastrino = get_new_idmastrino();
        $descrizione = post('descrizione');

        // Lettura info fattura
        $query = 'SELECT *, co_documenti.note, co_documenti.idpagamento, co_documenti.id AS iddocumento, co_statidocumento.descrizione AS `stato`, co_tipidocumento.descrizione AS `descrizione_tipodoc` FROM ((co_documenti LEFT OUTER JOIN co_statidocumento ON co_documenti.idstatodocumento=co_statidocumento.id) INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id='.prepare($iddocumento);
        $rs = $dbo->fetchArray($query);
        $ragione_sociale = $rs[0]['ragione_sociale'];
        $data_documento = $rs[0]['data'];
        $totale = 0;
        $totale_pagato = 0;

        for ($i = 0; $i < sizeof(post('idconto')); ++$i) {
            $idconto = post('idconto')[$i];
            $dare = post('dare')[$i];
            $avere = post('avere')[$i];

            if (!empty($dare) || !empty($avere)) {
                if (!empty($avere)) {
                    $totale = -$avere;
                } else {
                    $totale = $dare;

                    $totale_pagato += $totale;
                }

                $query = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', '.prepare($iddocumento).', '.prepare($descrizione).', '.prepare($idconto).', '.prepare($totale).", '1')";
                if (!$dbo->query($query)) {
                    $all_ok = false;
                } else {
                    $all_ok = true;
                    $id_record = $dbo->lastInsertedID();
                }
            }
        }

        // Inserisco nello scadenziario il totale pagato
        if ($totale_pagato != 0) {
            aggiorna_scadenziario($iddocumento, abs($totale_pagato), $data);
        }

        // Se non va a buon fine qualcosa elimino il mastrino per non lasciare incongruenze nel db
        if (!$all_ok) {
            flash()->error(tr("Errore durante l'aggiunta del movimento!"));
            $dbo->query('DELETE FROM co_movimenti WHERE idmastrino='.prepare($idmastrino));
        } else {
            flash()->info(tr('Movimento aggiunto in prima nota!'));

            // Verifico se la fattura è stata pagata tutta, così imposto lo stato a "Pagato"
            $query = 'SELECT SUM(pagato) AS tot_pagato, SUM(da_pagare) AS tot_da_pagare FROM co_scadenziario GROUP BY iddocumento HAVING iddocumento='.prepare($iddocumento);
            $rs = $dbo->fetchArray($query);

            // Aggiorno lo stato della fattura
            if (abs($rs[0]['tot_pagato']) == abs($rs[0]['tot_da_pagare'])) {
                $dbo->query("UPDATE co_documenti SET idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione='Pagato') WHERE id=".prepare($iddocumento));
            } else {
                $dbo->query("UPDATE co_documenti SET idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione='Parzialmente pagato') WHERE id=".prepare($iddocumento));
            }

            // Aggiorno lo stato dei preventivi collegati alla fattura se ce ne sono
            $query2 = 'SELECT idpreventivo FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento).' AND NOT idpreventivo=0 AND idpreventivo IS NOT NULL';
            $rs2 = $dbo->fetchArray($query2);

            for ($j = 0; $j < sizeof($rs2); ++$j) {
                $dbo->query("UPDATE co_preventivi SET idstato=(SELECT id FROM co_statipreventivi WHERE descrizione='Pagato') WHERE id=".prepare($rs2[$j]['idpreventivo']));
            }

            // Aggiorno lo stato dei contratti collegati alla fattura se ce ne sono
            $query2 = 'SELECT idcontratto FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento).' AND NOT idcontratto=0 AND idcontratto IS NOT NULL';
            $rs2 = $dbo->fetchArray($query2);
            for ($j = 0; $j < sizeof($rs2); ++$j) {
                $dbo->query("UPDATE co_contratti SET idstato=(SELECT id FROM co_staticontratti WHERE descrizione='Pagato') WHERE id=".prepare($rs2[$j]['idcontratto']));
            }

            // Aggiorno lo stato degli interventi collegati alla fattura se ce ne sono
            $query2 = 'SELECT idintervento FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento).' AND idintervento IS NOT NULL';
            $rs2 = $dbo->fetchArray($query2);

            for ($j = 0; $j < sizeof($rs2); ++$j) {
                $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Fatturato') WHERE id IN (SELECT idintervento FROM co_preventivi_interventi WHERE idpreventivo=".prepare($rs2[$j]['idpreventivo']).')');
            }
        }

        //Creo il modello di prima nota

        if (post('crea_modello') == '1') {
            $idmastrino = get_new_idmastrino('co_movimenti_modelli');

            for ($i = 0; $i < sizeof(post('idconto')); ++$i) {
                $idconto = post('idconto')[$i];
                $query = 'INSERT INTO co_movimenti_modelli(idmastrino, descrizione, idconto) VALUES('.prepare($idmastrino).', '.prepare($descrizione).', '.prepare($idconto).')';
                $dbo->query($query);
            }
        }

        break;

    case 'editriga':
        $all_ok = true;
        $iddocumento = post('iddocumento');
        $data = post('data');
        $idmastrino = post('idmastrino');
        $descrizione = post('descrizione');

        // Leggo il totale di questo mastrino
        $query = 'SELECT totale FROM co_movimenti WHERE idmastrino='.prepare($idmastrino).' AND primanota=1 AND totale>0';
        $rs = $dbo->fetchArray($query);
        $tot_mastrino = 0.00;

        for ($i = 0; $i < sizeof($rs); ++$i) {
            $tot_mastrino += abs($rs[0]['totale']);
        }

        // Eliminazione prima nota
        $dbo->query('DELETE FROM co_movimenti WHERE idmastrino='.prepare($idmastrino).' AND primanota=1');

        // Lettura info fattura
        $query = 'SELECT *, co_documenti.note, co_documenti.idpagamento, co_documenti.id AS iddocumento, co_statidocumento.descrizione AS `stato`, co_tipidocumento.descrizione AS `descrizione_tipodoc` FROM ((co_documenti LEFT OUTER JOIN co_statidocumento ON co_documenti.idstatodocumento=co_statidocumento.id) INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id='.prepare($iddocumento);
        $rs = $dbo->fetchArray($query);
        $ragione_sociale = $rs[0]['ragione_sociale'];
        $dir = $rs[0]['dir'];

        for ($i = 0; $i < sizeof(post('idconto')); ++$i) {
            $idconto = post('idconto')[$i];
            $dare = post('dare')[$i];
            $avere = post('avere')[$i];

            if ($dare != '' && $dare != 0) {
                $totale = $dare;
            } elseif ($avere != '' && $avere != 0) {
                $totale = -$avere;
            } else {
                $totale = 0;
            }

            if ($totale != 0) {
                $query = 'INSERT INTO co_movimenti(idmastrino, data, iddocumento, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($iddocumento).', '.prepare($descrizione).', '.prepare($idconto).', '.prepare($totale).", '1')";

                if (!$dbo->query($query)) {
                    $all_ok = false;
                } else {
                    $id_record = $dbo->lastInsertedID();
                    /*
                        Devo azzerare il totale pagato nello scadenziario perché verrà ricalcolato.
                        Se c'erano delle rate già pagate ne devo tener conto per rigenerare il totale pagato
                    */
                    // Tengo conto dei valori negativi per gli acquisti e dei valori positivi per le vendite
                    if (($dir == 'uscita' && $totale < 0) || ($dir == 'entrata' && $totale > 0)) {
                        // Azzero lo scadenziario e lo ricalcolo
                        $dbo->query("UPDATE co_scadenziario SET pagato=0, data_pagamento='0000-00-00' WHERE iddocumento=".prepare($iddocumento));

                        // Ricalcolo lo scadenziario per il solo nuovo importo
                        aggiorna_scadenziario($iddocumento, $totale, $data);

                        // Se il totale pagato non è il totale da pagare rimetto la fattura in stato "Emessa"
                        $query2 = 'SELECT SUM(pagato) AS tot_pagato, SUM(da_pagare) AS tot_da_pagare FROM co_scadenziario WHERE iddocumento='.prepare($iddocumento);
                        $rs2 = $dbo->fetchArray($query2);

                        // Aggiorno lo stato della fattura a "Emessa"
                        if (abs($rs2[0]['tot_pagato']) < abs($rs2[0]['tot_da_pagare'])) {
                            $dbo->query("UPDATE co_documenti SET idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione='Emessa') WHERE id=".prepare($iddocumento));

                            // Aggiorno lo stato dei preventivi collegati alla fattura se ce ne sono
                            $query3 = 'SELECT idpreventivo FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento).' AND NOT idpreventivo=0 AND idpreventivo IS NOT NULL';
                            $rs3 = $dbo->fetchArray($query3);

                            for ($j = 0; $j < sizeof($rs3); ++$j) {
                                $dbo->query("UPDATE co_preventivi SET idstato=(SELECT id FROM co_statipreventivi WHERE descrizione='In attesa di pagamento') WHERE id=".prepare($rs3[$j]['idpreventivo']));

                                // Aggiorno anche lo stato degli interventi collegati ai preventivi
                                $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Completato') WHERE id IN(SELECT idintervento FROM co_preventivi_interventi WHERE idpreventivo=".prepare($rs3[$j]['idpreventivo']).')');
                            }

                            // Aggiorno lo stato degli interventi collegati alla fattura se ce ne sono
                            $query3 = 'SELECT idintervento FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento).' AND idintervento IS NOT NULL';
                            $rs3 = $dbo->fetchArray($query3);

                            for ($j = 0; $j < sizeof($rs3); ++$j) {
                                $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Fatturato') WHERE id=".prepare($rs3[$j]['idintervento']));
                            }
                        }
                    }
                }
            }
        }

        // Se non va a buon fine qualcosa elimino il mastrino per non lasciare incongruenze nel db
        if (!$all_ok) {
            flash()->error(tr("Errore durante l'aggiunta del movimento!"));
            $dbo->query('DELETE FROM co_movimenti WHERE idmastrino='.prepare($idmastrino));
        } else {
            flash()->info(tr('Movimento modificato in prima nota!'));

            // Verifico se la fattura è stata pagata, così imposto lo stato a "Pagato"
            $query = 'SELECT SUM(pagato) AS tot_pagato, SUM(da_pagare) AS tot_da_pagare FROM co_scadenziario GROUP BY iddocumento HAVING iddocumento='.prepare($iddocumento);
            $rs = $dbo->fetchArray($query);

            // Aggiorno lo stato della fattura
            if ($rs[0]['tot_pagato'] == $rs[0]['tot_da_pagare']) {
                $stato = 'Pagato';
            } else {
                $stato = 'Parzialmente pagato';
            }

            $dbo->query('UPDATE co_documenti SET idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione='.prepare($stato).') WHERE id='.prepare($iddocumento));

            // Aggiorno lo stato dei preventivi collegati alla fattura se ce ne sono
            $query2 = 'SELECT idpreventivo FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento).' AND NOT idpreventivo=0 AND idpreventivo IS NOT NULL';
            $rs2 = $dbo->fetchArray($query2);

            for ($j = 0; $j < sizeof($rs2); ++$j) {
                $dbo->query("UPDATE co_preventivi SET idstato=(SELECT id FROM co_statipreventivi WHERE descrizione='Pagato') WHERE id=".prepare($rs2[$j]['idpreventivo']));

                // Aggiorno anche lo stato degli interventi collegati ai preventivi
                $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Fatturato') WHERE id IN (SELECT idintervento FROM co_preventivi_interventi WHERE idpreventivo=".prepare($rs2[$j]['idpreventivo']).')');
            }

            // Aggiorno lo stato degli interventi collegati alla fattura se ce ne sono
            $query2 = 'SELECT idintervento FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento).' AND idintervento IS NOT NULL';
            $rs2 = $dbo->fetchArray($query2);

            for ($j = 0; $j < sizeof($rs2); ++$j) {
                $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Fatturato') WHERE id IN (SELECT idintervento FROM co_preventivi_interventi WHERE idpreventivo=".prepare($rs2[$j]['idpreventivo']).')');
            }
        }
        break;

    // eliminazione movimento prima nota
    case 'delete':
        $idmastrino = post('idmastrino');

        if ($idmastrino != '') {
            // Leggo l'id della fattura per azzerare i valori di preventivi e interventi collegati
            $query = 'SELECT iddocumento FROM co_movimenti WHERE idmastrino='.prepare($idmastrino).' AND primanota=1';
            $rs = $dbo->fetchArray($query);
            $iddocumento = $rs[0]['iddocumento'];

            // Leggo il totale dal mastrino e lo rimuovo dal totale pagato dello scadenziario, ciclando tra le rate
            $query = 'SELECT totale FROM co_movimenti WHERE idmastrino='.prepare($idmastrino).' AND primanota=1 AND totale>0';
            $rs = $dbo->fetchArray($query);
            $totale_mastrino = 0.00;

            for ($i = 0; $i < sizeof($rs); ++$i) {
                $totale_mastrino += $rs[0]['totale'];
            }

            $rimanente = $totale_mastrino;

            $query = 'SELECT * FROM co_scadenziario WHERE iddocumento='.prepare($iddocumento).' ORDER BY scadenza DESC';
            $rs = $dbo->fetchArray($query);

            for ($i = 0; $i < sizeof($rs); ++$i) {
                if (abs($rimanente) > 0) {
                    if (abs($rs[$i]['pagato']) >= abs($rimanente)) {
                        $query2 = 'SELECT pagato FROM co_scadenziario WHERE id='.prepare($rs[$i]['id']);
                        $rs2 = $dbo->fetchArray($query2);
                        $pagato = $rs2[0]['pagato'];

                        ($pagato < 0) ? $sign = -1 : $sign = 1;
                        $new_value = ((abs($pagato) - abs($rimanente)) * $sign);

                        // Se resta ancora un po' di pagato cambio solo l'importo...
                        if ($new_value > 0) {
                            $dbo->query('UPDATE co_scadenziario SET pagato='.prepare($new_value).' WHERE id='.prepare($rs[$i]['id']));
                        }

                        // ...se l'importo è a zero, azzero anche la data di pagamento
                        else {
                            $dbo->query('UPDATE co_scadenziario SET pagato='.prepare($new_value).", data_pagamento='0000-00-00' WHERE id=".prepare($rs[$i]['id']));
                        }

                        $rimanente = 0;
                    } else {
                        $dbo->query("UPDATE co_scadenziario SET pagato='0', data_pagamento='0000-00-00' WHERE id=".prepare($rs[$i]['id']));
                        $rimanente -= abs($rs[$i]['pagato']);
                    }
                }
            }

            // Eliminazione prima nota
            $dbo->query('DELETE FROM co_movimenti WHERE idmastrino='.prepare($idmastrino).' AND primanota=1');

            // Aggiorno lo stato della fattura a "Emessa" o "Parzialmente pagato"
            $rs_pagamenti = $dbo->fetchArray("SELECT SUM(pagato) AS pagato FROM co_scadenziario WHERE iddocumento='".$iddocumento."'");
            if ($rs_pagamenti[0]['pagato'] > 0) {
                $dbo->query("UPDATE co_documenti SET idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione='Parzialmente pagato') WHERE id=".prepare($iddocumento));
            } else {
                $dbo->query("UPDATE co_documenti SET idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione='Emessa') WHERE id=".prepare($iddocumento));
            }

            // Aggiorno lo stato dei preventivi collegati alla fattura se ce ne sono
            $query = 'SELECT idpreventivo FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento).' AND NOT idpreventivo=0 AND idpreventivo IS NOT NULL';
            $rs = $dbo->fetchArray($query);

            for ($i = 0; $i < sizeof($rs); ++$i) {
                $dbo->query("UPDATE co_preventivi SET idstato=(SELECT id FROM co_statipreventivi WHERE descrizione='In attesa di pagamento') WHERE id=".prepare($rs[$i]['idpreventivo']));

                // Aggiorno anche lo stato degli interventi collegati ai preventivi
                $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Completato') WHERE idpreventivo=".prepare($rs[$i]['idpreventivo']));
            }

            // Aggiorno lo stato degli interventi collegati alla fattura se ce ne sono
            $query = 'SELECT idintervento FROM co_righe_documenti WHERE iddocumento='.prepare($iddocumento).' AND NOT idintervento IS NOT NULL';
            $rs = $dbo->fetchArray($query);

            for ($i = 0; $i < sizeof($rs); ++$i) {
                $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Fatturato') WHERE id=".prepare($rs[$i]['idintervento']));
            }

            flash()->info(tr('Movimento eliminato!'));
        }
        break;
}
