<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Fatture\Components\Articolo;
use Modules\Fatture\Components\Descrizione;
use Modules\Fatture\Components\Riga;
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

        flash()->info(tr('Aggiunta fattura numero _NUM_!', [
            '_NUM_' => $fattura->numero,
        ]));

        break;

    case 'update':
        if (post('id_record') !== null) {
            $idstatodocumento = post('idstatodocumento');
            $idpagamento = post('idpagamento');

            $totale_imponibile = get_imponibile_fattura($id_record);
            $totale_fattura = get_totale_fattura($id_record);

            $data = [];
            if ($dir == 'uscita') {
                $data = [
                    'numero' => post('numero'),
                    'numero_esterno' => post('numero_esterno'),
                    'idrivalsainps' => post('id_rivalsa_inps'),
                    'idritenutaacconto' => post('id_ritenuta_acconto'),
                ];
            }

            // Leggo la descrizione del pagamento
            $query = 'SELECT descrizione FROM co_pagamenti WHERE id='.prepare($idpagamento);
            $rs = $dbo->fetchArray($query);
            $pagamento = $rs[0]['descrizione'];

            // Query di aggiornamento
            $dbo->update('co_documenti', array_merge([
                'data' => post('data'),
                'numero_esterno' => post('numero_esterno'),
                'note' => post('note'),
                'note_aggiuntive' => post('note_aggiuntive'),

                'idstatodocumento' => $idstatodocumento,
                'idtipodocumento' => post('idtipodocumento'),
                'idanagrafica' => post('idanagrafica'),
                'idagente' => post('idagente'),
                'idpagamento' => $idpagamento,
                'idbanca' => post('idbanca'),
                'idcausalet' => post('idcausalet'),
                'idspedizione' => post('idspedizione'),
                'idporto' => post('idporto'),
                'idaspettobeni' => post('idaspettobeni'),
                'idvettore' => post('idvettore'),
                'idsede' => post('idsede'),
                'idconto' => post('idconto'),
                'split_payment' => post('split_payment') ?: 0,

                'n_colli' => post('n_colli'),
                'tipo_resa' => post('tipo_resa'),
                'bollo' => 0,
                'rivalsainps' => 0,
                'ritenutaacconto' => 0,
                'iva_rivalsainps' => 0,
                'codice_stato_fe' => post('codice_stato_fe') ?: null,
            ], $data), ['id' => $id_record]);

            $query = 'SELECT descrizione FROM co_statidocumento WHERE id='.prepare($idstatodocumento);
            $rs = $dbo->fetchArray($query);

            // Aggiornamento sconto
            if ($record['stato'] != 'Pagato' && $record['stato'] != 'Emessa') {
                $dbo->update('co_documenti', [
                    'tipo_sconto_globale' => post('tipo_sconto_generico'),
                    'sconto_globale' => post('sconto_generico'),
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
                ricalcola_costiagg_fattura($id_record, $idrivalsainps, $idritenutaacconto, post('bollo'));
            }

            // Elimino la scadenza e tutti i movimenti, poi se la fattura è emessa le ricalcolo
            if ($rs[0]['descrizione'] == 'Bozza' or $rs[0]['descrizione'] == 'Annullata') {
                elimina_scadenza($id_record);
                elimina_movimento($id_record, 0);
                // elimino movimento anche prima nota (se pagata o parzialmente pagata)
                elimina_movimento($id_record, 1);
            } elseif ($rs[0]['descrizione'] == 'Emessa') {
                elimina_scadenza($id_record);
                elimina_movimento($id_record, 0);
            } elseif (($rs[0]['descrizione'] == 'Pagato' or $rs[0]['descrizione'] == 'Parzialmente pagato') and ($dbo->fetchNum('SELECT id  FROM co_scadenziario WHERE iddocumento = '.prepare($id_record)) == 0)) {
                // aggiungo la scadenza come già pagata
                aggiungi_scadenza($id_record, $pagamento, 1);
                aggiungi_movimento($id_record, $dir);
            }

            // Se la fattura è in stato "Emessa" posso inserirla in scadenzario e aprire il mastrino cliente
            if ($rs[0]['descrizione'] == 'Emessa') {
                aggiungi_scadenza($id_record, $pagamento);
                aggiungi_movimento($id_record, $dir);
            }

            flash()->info(tr('Fattura modificata correttamente!'));
        }

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
        }

        // Se ci sono degli interventi collegati li rimetto nello stato "Completato"
        $rs = $dbo->fetchArray('SELECT idintervento FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idintervento IS NOT NULL');
        for ($i = 0; $i < sizeof($rs); ++$i) {
            $dbo->query("UPDATE in_interventi SET idstatointervento='OK' WHERE id=".prepare($rs[$i]['idintervento']));
        }

        $dbo->query('DELETE FROM co_documenti WHERE id='.prepare($id_record));
        $dbo->query('DELETE FROM co_scadenziario WHERE iddocumento='.prepare($id_record));
        $dbo->query('DELETE FROM co_movimenti WHERE iddocumento='.prepare($id_record));

        // Azzeramento collegamento della rata contrattuale alla pianificazione
        $dbo->query('UPDATE co_ordiniservizio_pianificazionefatture SET iddocumento=0 WHERE iddocumento='.prepare($id_record));

        elimina_scadenza($id_record);
        elimina_movimento($id_record);

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
            $dbo->query('INSERT INTO co_documenti(numero, numero_esterno, data, idanagrafica, idcausalet, idspedizione, idporto, idaspettobeni, idvettore, n_colli, idsede, idtipodocumento, idstatodocumento, idpagamento, idconto, idrivalsainps, idritenutaacconto, rivalsainps, iva_rivalsainps, ritenutaacconto, bollo, note, note_aggiuntive, buono_ordine, id_segment) VALUES('.prepare($numero).', '.prepare($numero_esterno).', '.prepare($rs[0]['data']).', '.prepare($rs[0]['idanagrafica']).', '.prepare($rs[0]['idcausalet']).', '.prepare($rs[0]['idspedizione']).', '.prepare($rs[0]['idporto']).', '.prepare($rs[0]['idaspettobeni']).', '.prepare($rs[0]['idvettore']).', '.prepare($rs[0]['n_colli']).', '.prepare($rs[0]['idsede']).', '.prepare($rs[0]['idtipodocumento']).', (SELECT id FROM co_statidocumento WHERE descrizione=\'Bozza\'), '.prepare($rs[0]['idpagamento']).', '.prepare($rs[0]['idconto']).', '.prepare($rs[0]['idrivalsainps']).', '.prepare($rs[0]['idritenutaacconto']).', '.prepare($rs[0]['rivalsainps']).', '.prepare($rs[0]['iva_rivalsainps']).', '.prepare($rs[0]['ritenutaacconto']).', '.prepare($rs[0]['bollo']).', '.prepare($rs[0]['note']).', '.prepare($rs[0]['note_aggiuntive']).', '.prepare($rs[0]['buono_ordine']).', '.prepare($rs[0]['id_segment']).')');
            $id_record = $dbo->lastInsertedID();

            // TODO: sistemare la duplicazione delle righe generiche e degli articoli, ignorando interventi, ddt, ordini, preventivi
            foreach ($righe as $riga) {
                // Scarico/carico nuovamente l'articolo da magazzino
                if (!empty($riga['idarticolo'])) {
                    add_articolo_infattura($id_record, $riga['idarticolo'], $riga['descrizione'], $riga['idiva'], $riga['qta'], $riga['subtotale'], $riga['sconto'], $riga['sconto_unitario'], $riga['tipo_sconto'], $riga['idintervento'], $riga['idconto'], $riga['um']);
                } else {
                    $dbo->query('INSERT INTO co_righe_documenti(iddocumento, idordine, idddt, idintervento, idarticolo, idpreventivo, idcontratto, is_descrizione, idtecnico, idagente, idautomezzo, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, idritenutaacconto, ritenutaacconto, idrivalsainps, rivalsainps, um, qta, `order`) VALUES('.prepare($id_record).', 0, 0, 0, '.prepare($riga['idarticolo']).', '.prepare($riga['idpreventivo']).', '.prepare($riga['idcontratto']).', '.prepare($riga['is_descrizione']).', '.prepare($riga['idtecnico']).', '.prepare($riga['idagente']).', '.prepare($riga['idautomezzo']).', '.prepare($riga['idconto']).', '.prepare($riga['idiva']).', '.prepare($riga['desc_iva']).', '.prepare($riga['iva']).', '.prepare($riga['iva_indetraibile']).', '.prepare($riga['descrizione']).', '.prepare($riga['subtotale']).', '.prepare($riga['sconto']).', '.prepare($riga['sconto_unitario']).', '.prepare($riga['tipo_sconto']).', '.prepare($riga['idritenutaacconto']).', '.prepare($riga['ritenutaacconto']).', '.prepare($riga['idrivalsainps']).', '.prepare($riga['rivalsainps']).', '.prepare($riga['um']).', '.prepare($riga['qta']).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))');
                }
            }

            // Ricalcolo inps, ritenuta e bollo (se la fattura non è stata pagata)
            if ($dir == 'entrata') {
                ricalcola_costiagg_fattura($id_record);
            } else {
                ricalcola_costiagg_fattura($id_record, $rs[0]['idrivalsainps'], $rs[0]['idritenutaacconto'], $rs[0]['bollo']);
            }

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
        if (!empty($id_record) && post('idintervento') !== null) {
            aggiungi_intervento_in_fattura(post('idintervento'), $id_record, post('descrizione'), post('idiva'), post('idconto'), post('id_rivalsa_inps'), post('id_ritenuta_acconto'), post('calcolo_ritenuta_acconto'));

            flash()->info(tr('Intervento _NUM_ aggiunto!', [
                '_NUM_' => $idintervento,
            ]));
        }
        break;

    case 'addpreventivo':
        if (!empty($id_record) && post('idpreventivo') !== null) {
            $idpreventivo = post('idpreventivo');
            $descrizione = post('descrizione');
            $idiva = post('idiva');
            $idconto = post('idconto');

            $prezzo = post('prezzo');
            $qta = 1;

            // Calcolo dello sconto
            $sconto_unitario = post('sconto');
            $tipo_sconto = post('tipo_sconto');
            $sconto = calcola_sconto([
                'sconto' => $sconto_unitario,
                'prezzo' => $prezzo,
                'tipo' => $tipo_sconto,
                'qta' => $qta,
            ]);

            $subtot = 0;
            $aggiorna_budget = (post('aggiorna_budget') == 'on') ? 1 : 0;

            // Leggo l'anagrafica del cliente
            $rs = $dbo->fetchArray('SELECT idanagrafica, numero FROM `co_preventivi` WHERE id='.prepare($idpreventivo));
            $idanagrafica = $rs[0]['idanagrafica'];
            $numero = $rs[0]['numero'];

            // Calcolo iva
            $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
            $rs = $dbo->fetchArray($query);
            $iva = ($prezzo - $sconto) / 100 * $rs[0]['percentuale'];
            $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
            $desc_iva = $rs[0]['descrizione'];

            // Calcolo rivalsa inps
            $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare(setting('Percentuale rivalsa INPS'));
            $rs = $dbo->fetchArray($query);
            $rivalsainps = ($prezzo - $sconto) / 100 * $rs[0]['percentuale'];

            // Calcolo ritenuta d'acconto TOTALE
            $query = 'SELECT * FROM co_ritenutaacconto WHERE id = '.prepare(setting("Percentuale ritenuta d'acconto"));
            $rs = $dbo->fetchArray($query);
            if (setting("Metodologia calcolo ritenuta d'acconto predefinito") == 'Imponibile') {
                $ritenutaacconto = ($prezzo - $sconto) / 100 * $rs[0]['percentuale'];
            } else {
                $ritenutaacconto = ($prezzo - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];
            }

            if (!empty(post('import'))) {
                // Replicazione delle righe del preventivo sul documento
                $righe = $dbo->fetchArray('SELECT idarticolo, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, um, qta, sconto, sconto_unitario, tipo_sconto, is_descrizione, IFNULL( (SELECT mg_articoli.abilita_serial FROM mg_articoli WHERE mg_articoli.id=co_righe_preventivi.idarticolo), 0 ) AS abilita_serial FROM co_righe_preventivi WHERE idpreventivo='.prepare($idpreventivo));

                foreach ($righe as $key => $riga) {
                    $subtot = $riga['subtotale'];

                    $sconto = $riga['sconto'];

                    // Ricalcolo ritenuta per ogni singola riga
                    if (setting("Metodologia calcolo ritenuta d'acconto predefinito") == 'Imponibile') {
                        $ritenutaacconto = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
                    } else {
                        $ritenutaacconto = ($subtot - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];
                    }

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
                        'is_descrizione' => $riga['is_descrizione'],
                        'sconto' => $riga['sconto'],
                        'sconto_unitario' => $riga['sconto_unitario'],
                        'tipo_sconto' => $riga['tipo_sconto'],
                        'order' => orderValue('co_righe_documenti', 'iddocumento', $id_record),
                        'idritenutaacconto' => setting("Percentuale ritenuta d'acconto"),
                        'ritenutaacconto' => $ritenutaacconto,
                        'idrivalsainps' => setting('Percentuale rivalsa INPS'),
                        'rivalsainps' => $rivalsainps,
                        'abilita_serial' => $riga['abilita_serial'],
                        'calcolo_ritenuta_acconto' => setting("Metodologia calcolo ritenuta d'acconto predefinito"),
                    ]);

                    if (!empty($riga['idarticolo'])) {
                        add_movimento_magazzino($riga['idarticolo'], -$riga['qta'], ['iddocumento' => $id_record]);
                    }
                }
            } else {
                // Aggiunta riga preventivo sul documento
                $query = 'INSERT INTO co_righe_documenti(iddocumento, idpreventivo, is_preventivo, idconto, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idritenutaacconto, ritenutaacconto, idrivalsainps, rivalsainps, `order`) VALUES('.prepare($id_record).', '.prepare($idpreventivo).', "1", '.prepare($idconto).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($prezzo).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).", '-', 1, ".prepare(setting("Percentuale ritenuta d'acconto")).', '.prepare($ritenutaacconto).', '.prepare(setting('Percentuale rivalsa INPS')).', '.prepare($rivalsainps).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';
                $dbo->query($query);

                // Scarico gli articoli nel preventivo
                $righe = $dbo->fetchArray('SELECT * FROM co_righe_preventivi WHERE idpreventivo='.prepare($idpreventivo));
                foreach ($righe as $key => $riga) {
                    if (!empty($riga['idarticolo'])) {
                        add_movimento_magazzino($riga['idarticolo'], -$riga['qta'], ['iddocumento' => $id_record]);
                    }
                }
            }

            // Aggiorno lo stato degli interventi collegati al preventivo se ce ne sono
            $query2 = 'SELECT idpreventivo FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND NOT idpreventivo=0 AND idpreventivo IS NOT NULL';
            $rs2 = $dbo->fetchArray($query2);

            for ($j = 0; $j < sizeof($rs2); ++$j) {
                $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Fatturato') WHERE id_preventivo=".prepare($rs2[$j]['idpreventivo']));
            }

            flash()->info(tr('Preventivo _NUM_ aggiunto!', [
                '_NUM_' => $numero,
            ]));

            // Aggiorno il budget sul preventivo con l'importo inserito in fattura e imposto lo stato del preventivo "In attesa di pagamento" (se selezionato)
            if ($aggiorna_budget) {
                $dbo->query('UPDATE co_preventivi SET budget='.prepare($prezzo).' WHERE id='.prepare($idpreventivo));
            }
            $dbo->query("UPDATE co_preventivi SET idstato=(SELECT id FROM co_statipreventivi WHERE descrizione='In attesa di pagamento') WHERE id=".prepare($idpreventivo));

            // Ricalcolo inps, ritenuta e bollo
            if ($dir == 'entrata') {
                ricalcola_costiagg_fattura($id_record);
            } else {
                ricalcola_costiagg_fattura($id_record);
            }
        }

        break;

    case 'addcontratto':
        if (!empty($id_record) && post('idcontratto') !== null) {
            $idcontratto = post('idcontratto');
            $descrizione = post('descrizione');
            $idiva = post('idiva');
            $idconto = post('idconto');

            $prezzo = post('prezzo');
            $qta = 1;

            // Calcolo dello sconto
            $sconto_unitario = post('sconto');
            $tipo_sconto = post('tipo_sconto');
            $sconto = calcola_sconto([
                'sconto' => $sconto_unitario,
                'prezzo' => $prezzo,
                'tipo' => $tipo_sconto,
                'qta' => $qta,
            ]);

            $subtot = 0;
            $aggiorna_budget = (post('aggiorna_budget') == 'on') ? 1 : 0;

            // Leggo l'anagrafica del cliente
            $rs = $dbo->fetchArray('SELECT idanagrafica, numero FROM `co_contratti` WHERE id='.prepare($idcontratto));
            $idanagrafica = $rs[0]['idanagrafica'];
            $numero = $rs[0]['numero'];

            // Calcolo iva
            $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
            $rs = $dbo->fetchArray($query);
            $iva = ($prezzo - $sconto) / 100 * $rs[0]['percentuale'];
            $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
            $desc_iva = $rs[0]['descrizione'];

            // Calcolo rivalsa inps
            $query = 'SELECT * FROM co_rivalsainps WHERE id='.prepare(setting('Percentuale rivalsa INPS'));
            $rs = $dbo->fetchArray($query);
            $rivalsainps = ($prezzo - $sconto) / 100 * $rs[0]['percentuale'];

            // Calcolo ritenuta d'acconto
            $query = 'SELECT * FROM co_ritenutaacconto WHERE id='.prepare(setting("Percentuale ritenuta d'acconto"));
            $rs = $dbo->fetchArray($query);
            if (setting("Metodologia calcolo ritenuta d'acconto predefinito") == 'Imponibile') {
                $ritenutaacconto = ($prezzo - $sconto) / 100 * $rs[0]['percentuale'];
            } else {
                $ritenutaacconto = ($prezzo - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];
            }

            if (!empty(post('import'))) {
                // Replicazione delle righe del contratto sul documento
                $righe = $dbo->fetchArray('SELECT idarticolo, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, um, qta, sconto, sconto_unitario, tipo_sconto, IFNULL( (SELECT mg_articoli.abilita_serial FROM mg_articoli WHERE mg_articoli.id=co_righe_contratti.idarticolo), 0 ) AS abilita_serial FROM co_righe_contratti WHERE idcontratto='.prepare($idcontratto));

                foreach ($righe as $key => $riga) {
                    $subtot = $riga['subtotale'];

                    $sconto = $riga['sconto'];

                    // Ricalcolo ritenuta per ogni singola riga
                    if (setting("Metodologia calcolo ritenuta d'acconto predefinito") == 'Imponibile') {
                        $ritenutaacconto = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
                    } else {
                        $ritenutaacconto = ($subtot - $sconto + $rivalsainps) / 100 * $rs[0]['percentuale'];
                    }

                    $dbo->insert('co_righe_documenti', [
                        'iddocumento' => $id_record,
                        'idcontratto' => $idcontratto,
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
                        'tipo_sconto' => $riga['tipo_sconto'],
                        'order' => orderValue('co_righe_documenti', 'iddocumento', $id_record),
                        'idritenutaacconto' => setting("Percentuale ritenuta d'acconto"),
                        'ritenutaacconto' => $ritenutaacconto,
                        'idrivalsainps' => setting('Percentuale rivalsa INPS'),
                        'rivalsainps' => $rivalsainps,
                        'abilita_serial' => $riga['abilita_serial'],
                        'calcolo_ritenuta_acconto' => setting("Metodologia calcolo ritenuta d'acconto predefinito"),
                    ]);

                    if (!empty($riga['idarticolo'])) {
                        add_movimento_magazzino($riga['idarticolo'], -$riga['qta'], ['iddocumento' => $id_record]);
                    }
                }
            } else {
                // Aggiunta riga contratto sul documento
                $query = 'INSERT INTO co_righe_documenti(iddocumento, idcontratto, is_contratto, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto, calcolo_ritenuta_acconto, `order`) VALUES('.prepare($id_record).', '.prepare($idcontratto).', "1", '.prepare($idconto).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($prezzo).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).", '-', 1, ".prepare(setting('Percentuale rivalsa INPS')).', '.prepare($rivalsainps).', '.prepare(setting("Percentuale ritenuta d'acconto")).', '.prepare($ritenutaacconto).', '.prepare(setting("Metodologia calcolo ritenuta d'acconto predefinito")).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';
                $dbo->query($query);

                // Scalo le qta degli articoli nel contratto
                $righe = $dbo->fetchArray('SELECT * FROM co_righe_contratti WHERE idcontratto='.prepare($idcontratto));
                foreach ($righe as $key => $riga) {
                    if (!empty($riga['idarticolo'])) {
                        add_movimento_magazzino($riga['idarticolo'], -$riga['qta'], ['iddocumento' => $id_record]);
                    }
                }
            }

            flash()->info(tr('Contratto _NUM_ aggiunto!', [
                '_NUM_' => $numero,
            ]));

            // Aggiorno lo stato degli interventi collegati al contratto se ce ne sono
            $query2 = 'SELECT idcontratto FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND NOT idcontratto=0 AND idcontratto IS NOT NULL';
            $rs2 = $dbo->fetchArray($query2);

            for ($j = 0; $j < sizeof($rs2); ++$j) {
                $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Fatturato') WHERE id_contratto=".prepare($rs2[$j]['idcontratto']));
            }

            // Aggiorno il budget sul contratto con l'importo inserito in fattura e imposto lo stato del contratto "In attesa di pagamento" (se selezionato)
            if ($aggiorna_budget) {
                $dbo->query('UPDATE co_contratti SET budget='.prepare($prezzo).' WHERE id='.prepare($idcontratto));
            }

            $dbo->query("UPDATE co_contratti SET idstato=(SELECT id FROM co_staticontratti WHERE descrizione='In attesa di pagamento') WHERE id=".prepare($idcontratto));

            // Ricalcolo inps, ritenuta e bollo
            if ($dir == 'entrata') {
                ricalcola_costiagg_fattura($id_record);
            } else {
                ricalcola_costiagg_fattura($id_record);
            }
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
        $um = post('um');
        if (!empty($um)) {
            $articolo->um = $um;
        }

        $articolo->id_iva = post('idiva');
        $articolo->idconto = post('idconto');

        if (post('calcolo_ritenuta_acconto')) {
            $articolo->calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto');
            $articolo->id_ritenuta_acconto = post('id_ritenuta_acconto');
        }

        if (post('id_rivalsa_inps')) {
            $articolo->id_rivalsa_inps = post('id_rivalsa_inps');
        }

        if (post('prezzo_acquisto')) {
            $riga->prezzo_unitario_acquisto = post('prezzo_acquisto');
        }
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

        flash()->info(tr('Articolo aggiunto!'));

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
        $um = post('um');
        if (!empty($um)) {
            $riga->um = $um;
        }

        $riga->id_iva = post('idiva');
        $riga->idconto = post('idconto');

        if (post('calcolo_ritenuta_acconto')) {
            $riga->calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto');
            $riga->id_ritenuta_acconto = post('id_ritenuta_acconto');
        }

        if (post('id_rivalsa_inps')) {
            $riga->id_rivalsa_inps = post('id_rivalsa_inps');
        }

        if (post('prezzo_acquisto')) {
            $riga->prezzo_unitario_acquisto = post('prezzo_acquisto');
        }
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

    // Creazione fattura da ddt
    case 'fattura_da_ddt':
        $totale_fattura = 0.00;
        $data = post('data');
        $idanagrafica = post('idanagrafica');
        $idarticolo = post('idarticolo');
        $idpagamento = post('idpagamento');
        $idddt = post('idddt');

        $id_segment = post('id_segment');
        $numero = get_new_numerofattura($data);

        if ($dir == 'entrata') {
            $numero_esterno = get_new_numerosecondariofattura($data);
        } else {
            $numero_esterno = '';
        }

        if ($dir == 'entrata') {
            $tipo_documento = 'Fattura differita di vendita';
            $idconto = setting('Conto predefinito fatture di vendita');
        } else {
            $tipo_documento = 'Fattura differita di acquisto';
            $idconto = setting('Conto predefinito fatture di acquisto');
        }

        // Creazione nuova fattura
        $dbo->query('INSERT INTO co_documenti(numero, numero_esterno, data, idanagrafica, idtipodocumento, idstatodocumento, idpagamento, idconto, id_segment) VALUES('.prepare($numero).', '.prepare($numero_esterno).', '.prepare($data).', '.prepare($idanagrafica).', (SELECT id FROM co_tipidocumento WHERE descrizione='.prepare($tipo_documento)."), (SELECT id FROM co_statidocumento WHERE descrizione='Bozza'), ".prepare($idpagamento).', '.prepare($idconto).', '.prepare($id_segment).' )');
        $id_record = $dbo->lastInsertedID();

        // Lettura di tutte le righe della tabella in arrivo
        foreach (post('qta_da_evadere') as $i => $value) {
            // Processo solo le righe da evadere
            if (post('evadere')[$i] == 'on') {
                $idrigaddt = $i;
                $idarticolo = post('idarticolo')[$i];
                $descrizione = post('descrizione')[$i];
                $qta = post('qta_da_evadere')[$i];
                $um = post('um')[$i];
                $subtot = post('subtot')[$i] * $qta;
                $sconto = post('sconto')[$i];
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

                $qdesc = 'SELECT is_descrizione FROM dt_righe_ddt WHERE id='.prepare($idrigaddt);
                $rsdesc = $dbo->fetchArray($qdesc);

                // Se sto aggiungendo un articolo uso la funzione per inserirlo e incrementare la giacenza
                if (!empty($idarticolo)) {
                    $idiva_acquisto = $idiva;
                    $prezzo_acquisto = $subtot;
                    $idriga = add_articolo_infattura($id_record, $idarticolo, $descrizione, $idiva_acquisto, $qta, $prezzo_acquisto, $sconto, $sconto_unitario, $tipo_sconto);

                    // Aggiornamento seriali dalla riga dell'ordine
                    $serials = is_array(post('serial')[$i]) ? post('serial')[$i] : [];
                    $serials = array_clean($serials);

                    $dbo->sync('mg_prodotti', ['id_riga_documento' => $idriga, 'dir' => $dir, 'id_articolo' => $idarticolo], ['serial' => $serials]);
                }

                // Inserimento riga normale
                else {
                    $query = 'INSERT INTO co_righe_documenti(iddocumento, idarticolo, descrizione, is_descrizione, idddt, idiva, desc_iva, iva, iva_indetraibile, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, `order`) VALUES('.prepare($id_record).', '.prepare($idarticolo).', '.prepare($descrizione).', '.prepare($rsdesc[0]['is_descrizione']).', '.prepare($idddt).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';

                    $dbo->query($query);
                }

                // Scalo la quantità dal ddt
                $dbo->query('UPDATE dt_righe_ddt SET qta_evasa = qta_evasa+'.$qta.' WHERE id='.prepare($idrigaddt));
            }
        }

        ricalcola_costiagg_fattura($id_record);

        flash()->info(tr('Creata una nuova fattura!'));

        break;

    // Creazione fattura da ordine
    case 'fattura_da_ordine':
        $totale_fattura = 0.00;
        $data = post('data');
        $idanagrafica = post('idanagrafica');
        $idarticolo = post('idarticolo');
        $idpagamento = post('idpagamento');
        $idconto = post('idconto');
        $idordine = post('idordine');
        $id_segment = post('id_segment');
        $numero = get_new_numerofattura($data);
        $numero_esterno = get_new_numerosecondariofattura($data);

        $tipo_documento = ($dir == 'entrata') ? 'Fattura immediata di vendita' : 'Fattura immediata di acquisto';

        // Creazione nuova fattura
        $dbo->query('INSERT INTO co_documenti(numero, numero_esterno, data, idanagrafica, idtipodocumento, idstatodocumento, idpagamento, idconto, id_segment) VALUES('.prepare($numero).', '.prepare($numero_esterno).', '.prepare($data).', '.prepare($idanagrafica).', (SELECT id FROM co_tipidocumento WHERE descrizione='.prepare($tipo_documento)."), (SELECT id FROM co_statidocumento WHERE descrizione='Bozza'), ".prepare($idpagamento).', '.prepare($idconto).','.prepare($id_segment).')');
        $id_record = $dbo->lastInsertedID();

        // Lettura di tutte le righe della tabella in arrivo
        foreach (post('qta_da_evadere') as $i => $value) {
            // Processo solo le righe da evadere
            if (post('evadere')[$i] == 'on') {
                $idriga = $i;
                $idarticolo = post('idarticolo')[$i];
                $descrizione = post('descrizione')[$i];
                $qta = post('qta_da_evadere')[$i];
                $um = post('um')[$i];
                $subtot = post('subtot')[$i] * $qta;
                $idiva = post('idiva')[$i];
                $iva = post('iva')[$i] * $qta;
                $sconto = post('sconto')[$i];
                $sconto = $sconto * $qta;

                $qprc = 'SELECT tipo_sconto, sconto_unitario FROM or_righe_ordini WHERE id='.prepare($idriga);
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

                $qdesc = 'SELECT is_descrizione FROM dt_righe_ddt WHERE id='.prepare($idriga);
                $rsdesc = $dbo->fetchArray($qdesc);

                // Se sto aggiungendo un articolo uso la funzione per inserirlo e incrementare la giacenza
                if (!empty($idarticolo)) {
                    $idiva_acquisto = $idiva;
                    $prezzo_acquisto = $subtot;
                    $riga = add_articolo_infattura($id_record, $idarticolo, $descrizione, $idiva_acquisto, $qta, $prezzo_acquisto, $sconto, $sconto_unitario, $tipo_sconto);

                    // Aggiornamento seriali dalla riga dell'ordine
                    $serials = is_array(post('serial')[$i]) ? post('serial')[$i] : [];
                    $serials = array_clean($serials);

                    $dbo->sync('mg_prodotti', ['id_riga_documento' => $riga, 'dir' => $dir, 'id_articolo' => $idarticolo], ['serial' => $serials]);

                    // Imposto la provenienza dell'ordine
                    $dbo->query('UPDATE co_righe_documenti SET idordine='.prepare($idordine).' WHERE id='.prepare($idriga));
                }

                // Inserimento riga normale
                else {
                    $dbo->query('INSERT INTO co_righe_documenti(iddocumento, idarticolo, idordine, idiva, desc_iva, iva, iva_indetraibile, descrizione, is_descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, `order`) VALUES('.prepare($id_record).', '.prepare($idarticolo).', '.prepare($idordine).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($rdesc[0]['is_descrizione']).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))');
                }

                // Scalo la quantità dall'ordine
                $dbo->query('UPDATE or_righe_ordini SET qta_evasa = qta_evasa+'.$qta.' WHERE id='.prepare($idriga));
            }
        }

        ricalcola_costiagg_fattura($id_record);
        flash()->info(tr('Creata una nuova fattura!'));

        break;

    // Creazione fattura da contratto
    case 'fattura_da_contratto':
        $idcontratto = post('id_record');
        $data = date('Y-m-d');

        $rs_segment = $dbo->fetchArray('SELECT * FROM zz_segments WHERE id_module='.prepare($id_module)." AND predefined='1'");
        $id_segment = $rs_segment[0]['id'];

        $numero = get_new_numerofattura($data);
        $numero_esterno = get_new_numerosecondariofattura($data);
        $tipo_documento = 'Fattura immediata di vendita';

        // Info contratto
        $rs_contratto = $dbo->fetchArray('SELECT * FROM co_contratti WHERE id='.prepare($idcontratto));
        $idanagrafica = $rs_contratto[0]['idanagrafica'];
        $idpagamento = $rs_contratto[0]['idpagamento'];
        $idconto = setting('Conto predefinito fatture di vendita');

        // Creazione nuova fattura
        $dbo->query('INSERT INTO co_documenti(numero, numero_esterno, data, idanagrafica, idtipodocumento, idstatodocumento, idpagamento, idconto, id_segment) VALUES('.prepare($numero).', '.prepare($numero_esterno).', '.prepare($data).', '.prepare($idanagrafica).', (SELECT id FROM co_tipidocumento WHERE descrizione='.prepare($tipo_documento)."), (SELECT id FROM co_statidocumento WHERE descrizione='Bozza'), ".prepare($idpagamento).', '.prepare($idconto).','.prepare($id_segment).')');
        $id_record = $dbo->lastInsertedID();

        // Righe contratto
        $rs_righe = $dbo->fetchArray('SELECT * FROM co_righe_contratti WHERE idcontratto='.prepare($idcontratto));

        for ($i = 0; $i < sizeof($rs_righe); ++$i) {
            // Se sto aggiungendo un articolo uso la funzione per inserirlo e incrementare la giacenza
            if ($rs_righe[$i]['idarticolo'] != 0) {
                add_articolo_infattura($id_record, $rs_righe[$i]['idarticolo'], $rs_righe[$i]['descrizione'], $rs_righe[$i]['idiva'], $rs_righe[$i]['qta'], $rs_righe[$i]['subtotale'], $rs_righe[$i]['sconto'], $rs_righe[$i]['sconto_unitario'], $rs_righe[$i]['tipo_sconto']);
            }

            // Inserimento riga normale
            else {
                $dbo->query('INSERT INTO co_righe_documenti(iddocumento, idcontratto, is_descrizione, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, sconto_globale, idiva, desc_iva, iva, iva_indetraibile, um, qta, idconto, `order`) values('.prepare($id_record).', '.prepare($idcontratto).', '.prepare($rs_righe[$i]['is_descrizione']).', '.prepare($rs_righe[$i]['descrizione']).', '.prepare($rs_righe[$i]['subtotale']).', '.prepare($rs_righe[$i]['sconto']).', '.prepare($rs_righe[$i]['sconto_unitario']).', '.prepare($rs_righe[$i]['tipo_sconto']).', '.prepare($rs_righe[$i]['sconto_globale']).', '.prepare($rs_righe[$i]['idiva']).', '.prepare($rs_righe[$i]['desc_iva']).', '.prepare($rs_righe[$i]['iva']).', '.prepare($rs_righe[$i]['iva_indetraibile']).', '.prepare($rs_righe[$i]['um']).', '.prepare($rs_righe[$i]['qta']).', '.prepare($idconto).', '.prepare($rs_righe[$i]['order']).')');
            }
        }

        flash()->info(tr('Creata una nuova fattura!'));
        break;

    // aggiungi righe da ddt
    case 'add_ddt':
        $idddt = post('iddocumento');

        $rs = $dbo->fetchArray('SELECT * FROM co_documenti WHERE id='.prepare($id_record));
        $idconto = $rs[0]['idconto'];

        // Lettura di tutte le righe della tabella in arrivo
        foreach (post('qta_da_evadere') as $i => $value) {
            // Processo solo le righe da evadere
            if (post('evadere')[$i] == 'on') {
                $idrigaddt = $i;
                $idarticolo = post('idarticolo')[$i];
                $descrizione = post('descrizione')[$i];

                $qta = post('qta_da_evadere')[$i];
                $um = post('um')[$i];

                $subtot = post('subtot')[$i] * $qta;
                $sconto = post('sconto')[$i];
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
                    $riga = add_articolo_infattura($id_record, $idarticolo, $descrizione, $idiva_acquisto, $qta, $prezzo_acquisto, 0, 0, 'UNT', 0, $idconto, $um);

                    // Lettura lotto, serial, altro dalla riga dell'ddt
                    $dbo->query('INSERT INTO mg_prodotti (id_riga_documento, id_articolo, dir, serial, lotto, altro) SELECT '.prepare($riga).', '.prepare($idarticolo).', '.prepare($dir).', serial, lotto, altro FROM mg_prodotti AS t WHERE id_riga_ddt='.prepare($idrigaddt));
                }

                // Inserimento riga normale
                else {
                    $query = 'INSERT INTO co_righe_documenti(iddocumento, idarticolo, descrizione, idconto, idddt, idiva, desc_iva, iva, iva_indetraibile, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, `order`) VALUES('.prepare($id_record).', '.prepare($idarticolo).', '.prepare($descrizione).', '.prepare($idconto).', '.prepare($idddt).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';
                    $dbo->query($query);
                }

                // Scalo la quantità dal ddt
                $dbo->query('UPDATE dt_righe_ddt SET qta_evasa = qta_evasa+'.$qta.' WHERE id='.prepare($idrigaddt));
            }
        }

        ricalcola_costiagg_fattura($id_record);

        flash()->info(tr('Aggiunti nuovi articoli in fattura!'));

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
            $dbo->query("UPDATE in_interventi SET idstatointervento='OK' WHERE id=".prepare($idintervento));

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

            flash()->info(tr('Articolo rimosso!'));
        }
        break;

    // Scollegamento preventivo da documento
    case 'unlink_preventivo':
        if (post('idriga') !== null) {
            $idriga = post('idriga');

            // Lettura preventivi collegati
            $query = 'SELECT iddocumento, idpreventivo, is_preventivo, idarticolo FROM co_righe_documenti WHERE id='.prepare($idriga);
            $rsp = $dbo->fetchArray($query);
            $id_record = $rsp[0]['iddocumento'];
            $idpreventivo = $rsp[0]['idpreventivo'];
            $is_preventivo = $rsp[0]['is_preventivo'];
            $idarticolo = $rsp[0]['idarticolo'];

            // preventivo su unica riga, perdo il riferimento dell'articolo quindi lo vado a leggere da co_righe_preventivi
            if (empty($idarticolo) && $is_preventivo) {
                // rimetto a magazzino gli articoli collegati al preventivo
                $rsa = $dbo->fetchArray('SELECT idarticolo, qta FROM co_righe_preventivi WHERE idpreventivo = '.prepare($idpreventivo));
                for ($i = 0; $i < sizeof($rsa); ++$i) {
                    if (!empty($rsa[$i]['idarticolo'])) {
                        add_movimento_magazzino($rsa[$i]['idarticolo'], $rsa[$i]['qta'], ['iddocumento' => $id_record]);
                    }
                }
            } else {
                if (!empty($idarticolo)) {
                    $rs5 = $dbo->fetchArray('SELECT idarticolo, id, qta FROM co_righe_documenti WHERE  id = '.prepare($idriga).'  AND idintervento IS NULL');
                    rimuovi_articolo_dafattura($rs5[0]['idarticolo'], $id_record, $idriga);
                }
            }

            $query = 'DELETE FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND id='.prepare($idriga);
            $dbo->query($query);

            $rs_righe = $dbo->fetchArray('SELECT * FROM co_righe_documenti WHERE idpreventivo='.prepare($idpreventivo));

            if (sizeof($rs_righe) == 0) {
                // Se ci sono dei preventivi collegati li rimetto nello stato "In attesa di pagamento"
                for ($i = 0; $i < sizeof($rsp); ++$i) {
                    $dbo->query("UPDATE co_preventivi SET idstato=(SELECT id FROM co_statipreventivi WHERE descrizione='In lavorazione') WHERE id=".prepare($rsp[$i]['idpreventivo']));

                    // Aggiorno anche lo stato degli interventi collegati ai preventivi
                    $dbo->query("UPDATE in_interventi SET idstatointervento='OK' WHERE id_preventivo=".prepare($rsp[$i]['idpreventivo']));
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
                }
            } else {
                if (!empty($idarticolo)) {
                    $rs5 = $dbo->fetchArray('SELECT idarticolo, id, qta FROM co_righe_documenti WHERE  id = '.prepare($idriga).'  AND idintervento IS NULL');
                    rimuovi_articolo_dafattura($rs5[0]['idarticolo'], $id_record, $idriga);
                }
            }

            $query = 'DELETE FROM co_righe_documenti WHERE iddocumento='.prepare($id_record).' AND idcontratto='.prepare($idcontratto);

            if ($dbo->query($query)) {
                // Se ci sono dei preventivi collegati li rimetto nello stato "In attesa di pagamento"
                for ($i = 0; $i < sizeof($rsp); ++$i) {
                    $dbo->query("UPDATE co_contratti SET idstato=(SELECT id FROM co_staticontratti WHERE descrizione='In lavorazione') WHERE id=".prepare($rsp[$i]['idcontratto']));

                    // Aggiorno anche lo stato degli interventi collegati ai contratti
                    $dbo->query("UPDATE in_interventi SET idstatointervento='OK' WHERE id IN (SELECT idintervento FROM co_promemoria WHERE idcontratto=".prepare($rsp[$i]['idcontratto']).')');
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

    // aggiungi righe da ordine
    case 'add_ordine':
        $idordine = post('iddocumento');

        // Lettura di tutte le righe della tabella in arrivo
        foreach (post('qta_da_evadere') as $i => $value) {
            // Processo solo le righe da evadere
            if (post('evadere')[$i] == 'on') {
                $idriga = $i;
                $idarticolo = post('idarticolo')[$i];
                $descrizione = post('descrizione')[$i];

                $qta = post('qta_da_evadere')[$i];
                $um = post('um')[$i];

                $subtot = post('subtot')[$i] * $qta;
                $sconto = post('sconto')[$i];
                $sconto = $sconto * $qta;

                $qprc = 'SELECT tipo_sconto, sconto_unitario FROM or_righe_ordini WHERE id='.prepare($idriga);
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

                $rs = $dbo->fetchArray('SELECT * FROM co_righe_documenti WHERE id='.prepare($idriga));
                $idconto = $rs[0]['idconto'];

                // Se sto aggiungendo un articolo uso la funzione per inserirlo e incrementare la giacenza
                if (!empty($idarticolo)) {
                    $idiva_acquisto = $idiva;
                    $prezzo_acquisto = $subtot;
                    $riga = add_articolo_infattura($id_record, $idarticolo, $descrizione, $idiva_acquisto, $qta, $prezzo_acquisto, $sconto, $sconto_unitario, $tipo_sconto, 0, $idconto);

                    // Lettura lotto, serial, altro dalla riga dell'ordine
                    $dbo->query('INSERT INTO mg_prodotti (id_riga_documento, id_articolo, dir, serial, lotto, altro) SELECT '.prepare($riga).', '.prepare($idarticolo).', '.prepare($dir).', serial, lotto, altro FROM mg_prodotti AS t WHERE id_riga_ordine='.prepare($idriga));
                }

                // Inserimento riga normale
                elseif ($qta != 0) {
                    $query = 'INSERT INTO co_righe_documenti(iddocumento, idarticolo, descrizione, idconto, idordine, idiva, desc_iva, iva, iva_indetraibile, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, `order`) VALUES('.prepare($id_record).', '.prepare($idarticolo).', '.prepare($descrizione).', '.prepare($idconto).', '.prepare($idordine).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';
                    $dbo->query($query);
                }

                // Scalo la quantità dall ordine
                $dbo->query('UPDATE or_righe_ordini SET qta_evasa = qta_evasa+'.$qta.' WHERE id='.prepare($idriga));
            }
        }

        ricalcola_costiagg_fattura($id_record);

        flash()->info(tr('Aggiunti nuovi articoli in fattura!'));

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
        $nota->idsede = $fattura->idsede;
        $nota->save();

        $id_record = $nota->id;

        // Lettura di tutte le righe della tabella in arrivo
        foreach (post('qta_da_evadere') as $i => $value) {
            // Processo solo le righe da evadere
            if (post('evadere')[$i] == 'on') {
                $idriga = $i;
                $idarticolo = post('idarticolo')[$i];
                $descrizione = post('descrizione')[$i];

                $qta = -post('qta_da_evadere')[$i];
                $um = post('um')[$i];

                $subtot = post('subtot')[$i] * $qta;
                $sconto = post('sconto')[$i];
                $sconto = $sconto * $qta;

                $qprc = 'SELECT tipo_sconto, sconto_unitario FROM co_righe_documenti WHERE id='.prepare($idriga);
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

                $qdesc = 'SELECT is_descrizione FROM co_righe_documenti WHERE id='.prepare($i);
                $rsdesc = $dbo->fetchArray($qdesc);

                // Se sto aggiungendo un articolo uso la funzione per inserirlo e incrementare la giacenza
                if (!empty($idarticolo)) {
                    $idiva_acquisto = $idiva;
                    $prezzo_acquisto = $subtot;
                    $riga = add_articolo_infattura($id_record, $idarticolo, $descrizione, $idiva_acquisto, $qta, $prezzo_acquisto, $sconto, $sconto_unitario, $tipo_sconto);

                    // Aggiornamento seriali dalla riga dell'ordine
                    $serials = is_array(post('serial')[$i]) ? post('serial')[$i] : [];
                    $serials = array_clean($serials);

                    $dbo->sync('mg_prodotti', ['id_riga_documento' => $riga, 'dir' => 'uscita', 'id_articolo' => $idarticolo], ['serial' => $serials]);
                    $dbo->detach('mg_prodotti', ['id_riga_documento' => $idriga, 'dir' => 'entrata', 'id_articolo' => $idarticolo], ['serial' => $serials]);
                }

                // Inserimento riga normale
                else {
                    $query = 'INSERT INTO co_righe_documenti(iddocumento, idarticolo, descrizione, idconto, idordine, idiva, desc_iva, iva, iva_indetraibile, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, is_descrizione, `order`) VALUES('.prepare($id_record).', '.prepare($idarticolo).', '.prepare($descrizione).', '.prepare($idconto).', '.prepare($idordine).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', '.prepare($rsdesc[0]['is_descrizione']).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))';
                    $dbo->query($query);

                    $riga = $dbo->lastInsertedID();
                }

                $dbo->query('UPDATE co_righe_documenti SET ref_riga_documento = '.prepare($idriga).' WHERE id='.prepare($riga));

                // Scalo la quantità dall ordine
                $dbo->query('UPDATE co_righe_documenti SET qta_evasa = qta_evasa+'.(-$qta).' WHERE id='.prepare($idriga));
            }
        }

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
    $nota->idsede = $fattura->idsede;
    $nota->save();

    $id_record = $nota->id;
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
