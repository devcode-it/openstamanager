<?php

if (file_exists(__DIR__.'/../../../core.php')) {
    include_once __DIR__.'/../../../core.php';
} else {
    include_once __DIR__.'/../../core.php';
}

include_once Modules::filepath('Fatture di vendita', 'modutil.php');

switch (post('op')) {
    case 'export-bulk':

        $dir = DOCROOT.'/files/export_interventi/';
        directory($dir.'tmp/');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione degli interventi da stampare
        $interventi = $dbo->fetchArray('SELECT in_interventi.id, in_interventi.codice, data_richiesta, ragione_sociale FROM in_interventi INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE in_interventi.id IN('.implode(',', $id_records).')');

        if (!empty($interventi)) {
            foreach ($interventi as $r) {
                $numero = $r['codice'];
                $numero = str_replace(['/', '\\'], '-', $numero);

                // Gestione della stampa
                $rapportino_nome = sanitizeFilename($numero.' '.date('Y_m_d', strtotime($r['data_richiesta'])).' '.$r['ragione_sociale'].'.pdf');
                $filename = slashes($dir.'tmp/'.$rapportino_nome);

                $print = Prints::getModuleMainPrint($id_module);

                Prints::render($print['id'], $r['id'], $filename);
            }

            $dir = slashes($dir);
            $file = slashes($dir.'interventi_'.time().'.zip');

            // Creazione zip
            if (extension_loaded('zip')) {
                create_zip($dir.'tmp/', $file);

                // Invio al browser dello zip
                download($file);

                // Rimozione dei contenuti
                delete($dir.'tmp/');
            }
        }

    break;

    case 'creafatturavendita':

        $rs_idanagrafica = $dbo->fetchArray('SELECT idanagrafica FROM in_interventi WHERE id='.prepare($id_records[0]));

        $idanagrafica = $rs_idanagrafica[0]['idanagrafica'];
        $data = date('Y-m-d');
        $dir = 'entrata';
        $idtipodocumento = '2';

        if (empty($_SESSION['module_'.Modules::get('Fatture di vendita')['id']]['id_segment'])) {
            $rs = $dbo->fetchArray('SELECT id  FROM zz_segments WHERE predefined = 1 AND id_module = '.prepare(Modules::get('Fatture di vendita')['id']).'LIMIT 0,1');
            $_SESSION['module_'.Modules::get('Fatture di vendita')['id']]['id_segment'] = $rs[0]['id'];
        }

        $id_segment = $_SESSION['module_'.Modules::get('Fatture di vendita')['id']]['id_segment'];

        $numero = get_new_numerofattura($data);

        $numero_esterno = get_new_numerosecondariofattura($data);
        $idconto = setting('Conto predefinito fatture di vendita');

        $campo = ($dir == 'entrata') ? 'idpagamento_vendite' : 'idpagamento_acquisti';

        // Tipo di pagamento predefinito dall'anagrafica
        $query = 'SELECT id FROM co_pagamenti WHERE id=(SELECT '.$campo.' AS pagamento FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica).')';
        $rs = $dbo->fetchArray($query);
        $idpagamento = $rs[0]['id'];

        // Se la fattura è di vendita e non è stato associato un pagamento predefinito al cliente leggo il pagamento dalle impostazioni
        if ($dir == 'entrata' && $idpagamento == '') {
            $idpagamento = setting('Tipo di pagamento predefinito');
        }

        $n_interventi = 0;

        //inserisco righe
        for ($i = 0; $i < sizeof($id_records); ++$i) {
            $idintervento = $id_records[$i];

            $q = 'SELECT id, in_interventi.descrizione, in_interventi.codice,
                IFNULL( (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento=in_interventi.id), data_richiesta) AS data,
                (SELECT costo_orario FROM in_tipiintervento WHERE idtipointervento=in_interventi.idtipointervento) AS costo_ore_unitario,
                (SELECT costo_km FROM in_tipiintervento WHERE idtipointervento=in_interventi.idtipointervento) AS costo_km_unitario,
                (SELECT SUM(prezzo_dirittochiamata) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS dirittochiamata,
                (SELECT SUM(km) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS km,
                (SELECT SUM(TIME_TO_SEC(TIMEDIFF(orario_fine, orario_inizio))) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS t1,
                (SELECT SUM(prezzo_ore_consuntivo) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_ore_consuntivo`,
                (SELECT SUM(prezzo_km_consuntivo) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_km_consuntivo`
                FROM in_interventi WHERE id="'.$idintervento.'" AND idanagrafica="'.$idanagrafica."\" AND id NOT IN (SELECT idintervento FROM co_righe_documenti WHERE idintervento != 'NULL') ";

            $rs = $dbo->fetchArray($q);

            if (count($rs) > 0) {
                //al primo ciclo preparo la fattura
                if ($n_interventi == 0) {
                    //preparo fattura
                    $dbo->query('INSERT INTO co_documenti (numero, numero_esterno, idanagrafica, idconto, idtipodocumento, idpagamento, data, idstatodocumento, idsede, id_segment) VALUES ('.prepare($numero).', '.prepare($numero_esterno).', '.prepare($idanagrafica).', '.prepare($idconto).', '.prepare($idtipodocumento).', '.prepare($idpagamento).', '.prepare($data).", (SELECT `id` FROM `co_statidocumento` WHERE `descrizione`='Bozza'), (SELECT idsede_fatturazione FROM an_anagrafiche WHERE idanagrafica=".prepare($idanagrafica).'), '.prepare($id_segment).')');
                    $iddocumento = $dbo->lastInsertedID();
                }

                ++$n_interventi;

                $subtot_consuntivo = $rs[0]['tot_ore_consuntivo'] + $rs[0]['tot_km_consuntivo'];

                //Calcolo sconto se è stato arrotondato il prezzo
                $subtot = $subtot_consuntivo;
                $sconto = 0;

                //Aggiungo un'eventuale sconto in base al listino del cliente
                if ($prc_sconto < 0) {
                    $sconto += $subtot / 100 * abs($prc_sconto);
                }

                $descrizione = 'Intervento numero '.$rs[0]['codice'].' del '.date('d/m/Y', strtotime($rs[0]['data']))."\n".html_entity_decode($rs[0]['descrizione']);

                //Aggiunta impianti
                $rsi = $dbo->fetchArray('SELECT * FROM my_impianti_interventi INNER JOIN my_impianti ON my_impianti.id=my_impianti_interventi.idimpianto WHERE idintervento="'.$rs[0]['id'].'"');
                if (sizeof($rsi) > 0) {
                    $descrizione .= "\nIMPIANTI:\n";

                    for ($b = 0; $b < sizeof($rsi); ++$b) {
                        $descrizione .= $rsi[$b]['matricola'].' - '.str_replace('&quot;', '&amp;quot;', $rsi[$b]['nome'])."\n";
                    }
                }
                //Aggiunta articoli utilizzati
                $rsa = $dbo->fetchArray('SELECT mg_articoli.descrizione, mg_articoli.codice, mg_articoli.prezzo_vendita, mg_articoli_interventi.qta, mg_articoli_interventi.sconto FROM mg_articoli_interventi INNER JOIN mg_articoli ON mg_articoli_interventi.idarticolo=mg_articoli.id WHERE idintervento="'.$rs[0]['id'].'"');

                if (sizeof($rsa) > 0) {
                    $descrizione .= "\nARTICOLI UTILIZZATI:\n";

                    for ($a = 0; $a < sizeof($rsa); ++$a) {
                        $descrizione .= $rsa[$a]['codice'].' - '.$rsa[$a]['descrizione'].' (x'.number_format($rsa[$a]['qta'], 2, ',', '.').")\n";
                        $subtot += $rsa[$a]['prezzo_vendita'];
                        $sconto += $rsa[$a]['sconto'] * $rsa[$a]['qta'];
                    }
                }

                //Aggiunta spese aggiuntive
                $rsa = $dbo->fetchArray('SELECT descrizione, qta, prezzo_vendita FROM in_righe_interventi WHERE idintervento="'.$rs[0]['id'].'"');

                if (sizeof($rsa) > 0) {
                    $descrizione .= "\nALTRI COSTI:\n";

                    for ($a = 0; $a < sizeof($rsa); ++$a) {
                        $descrizione .= $rsa[$a]['descrizione'].' (x'.number_format($rsa[$a]['qta'], 2, ',', '.').")\n";
                        $subtot += $rsa[$a]['prezzo'] * $rsa[$a]['qta'];
                        $sconto += $rsa[$a]['sconto'];
                    }
                }

                //Leggo l'anagrafica del cliente
                $rs = $dbo->fetchArray("SELECT idanagrafica, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento='".$rs[0]['id']."') AS data FROM `in_interventi` WHERE id='".$rs[0]['id']."'");
                $idanagrafica = $rs[0]['idanagrafica'];
                $data = $rs[0]['data'];

                //Calcolo iva
                $idiva = setting('Iva predefinita');
                $query = "SELECT * FROM co_iva WHERE id='".$idiva."'";
                $rs = $dbo->fetchArray($query);

                $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
                $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
                $desc_iva = $rs[0]['descrizione'];

                //Calcolo rivalsa inps
                $query = "SELECT * FROM co_rivalsainps WHERE id='".setting('Percentuale rivalsa INPS')."'";
                $rs = $dbo->fetchArray($query);
                $rivalsainps = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];

                //Calcolo ritenuta d'acconto
                $query = "SELECT * FROM co_ritenutaacconto WHERE id='".setting("Percentuale ritenuta d'acconto")."'";
                $rs = $dbo->fetchArray($query);
                $ritenutaacconto = ($subtot + $rivalsainps) / 100 * $rs[0]['percentuale'];

                //Aggiunta riga intervento sul documento
                $query1 = "INSERT INTO co_righe_documenti( iddocumento, idintervento, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, um, qta, idrivalsainps, rivalsainps, idritenutaacconto, ritenutaacconto ) VALUES( \"$iddocumento\", \"$idintervento\", \"".$idiva."\", \"$desc_iva\", \"$iva\", \"$iva_indetraibile\", \"$descrizione\", \"$subtot\", \"".$sconto.'", "ore", "1", "'.setting('Percentuale rivalsa INPS').'", "'.$rivalsainps.'", "'.setting("Percentuale ritenuta d'acconto").'", "'.$ritenutaacconto.'" )';
                if ($dbo->query($query1)) {
                    //Ricalcolo inps, ritenuta e bollo
                    if ($dir == 'entrata') {
                        ricalcola_costiagg_fattura($iddocumento);
                    } else {
                        ricalcola_costiagg_fattura($iddocumento, 0, 0, 0);
                    }

                    //Metto l'intervento in stato "Fatturato"
                    $dbo->query("UPDATE in_interventi SET idstatointervento='FAT' WHERE id='$idintervento'");
                }
            }
        }

        if ($n_interventi > 0) {
            flash()->info(tr('Fattura _NUM_ creata!', [
                '_NUM_' => $numero_esterno,
            ]));

            flash()->info(tr('_NUM_ interventi fatturati!', [
                '_NUM_' => $n_interventi,
            ]));
        } else {
            flash()->warning(tr('Nessuna attività fatturata!'));
        }

    break;
}

return [
    'export-bulk' => [
        'text' => tr('Esporta stampe'),
        'data' => [
            'msg' => tr('Vuoi davvero esportare queste stampe in un archivio?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ],

    'creafatturavendita' => [
        'text' => tr('Crea fattura'),
        'data' => [
            'msg' => tr('Vuoi davvero generare le fatture per questi interventi?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ],
];
