<?php

include_once __DIR__.'/core.php';

$module_name = $get['module'];

$op = $get['op'];

switch ($module_name) {
    case 'Anagrafiche':

        // Elenco nomi
        if ($op == 'get_ragione_sociale') {
            $ragione_sociale = $get['ragione_sociale'];
            $idagente = $get['idagente'];

            if ($idagente != '') {
                $WHERE_AGENTE = 'AND idagente = '.$idagente;
            }

            $q = "SELECT ragione_sociale FROM an_anagrafiche WHERE deleted=0 AND ragione_sociale LIKE '%$ragione_sociale%' ".$WHERE_AGENTE.' '.Modules::getAdditionalsQuery('Anagrafiche').' GROUP BY ragione_sociale ORDER BY ragione_sociale';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                echo htmlspecialchars_decode($rs[$i]['ragione_sociale'], ENT_QUOTES);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }

        // Elenco città
        elseif ($op == 'getcitta') {
            $q = 'SELECT DISTINCT(citta) FROM an_anagrafiche WHERE 1=1 '.Modules::getAdditionalsQuery('Anagrafiche').' ORDER BY citta';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                echo htmlspecialchars_decode($rs[$i]['citta'], ENT_QUOTES);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }

        // Elenco province
        elseif ($op == 'getprovince') {
            $q = 'SELECT DISTINCT(provincia) FROM an_anagrafiche WHERE 1=1 '.Modules::getAdditionalsQuery('Anagrafiche').' ORDER BY provincia';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                echo html_entity_decode($rs[$i]['provincia']);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }

        // Elenco cap
        elseif ($op == 'getcap') {
            $q = 'SELECT DISTINCT(cap) FROM an_anagrafiche WHERE 1=1 '.Modules::getAdditionalsQuery('Anagrafiche').' ORDER BY cap';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                echo html_entity_decode($rs[$i]['cap']);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }

        // Elenco settori
        elseif ($op == 'getsettori') {
            $q = 'SELECT DISTINCT(settore) FROM an_anagrafiche WHERE 1=1 '.Modules::getAdditionalsQuery('Anagrafiche').' ORDER BY settore';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                echo html_entity_decode($rs[$i]['settore'], ENT_QUOTES);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }

        // Elenco marche
        elseif ($op == 'getmarche') {
            $q = 'SELECT DISTINCT(marche) FROM an_anagrafiche WHERE 1=1 '.Modules::getAdditionalsQuery('Anagrafiche').' ORDER BY marche';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                echo html_entity_decode($rs[$i]['marche'], ENT_QUOTES);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }

        // Elenco e-mail - uso la funzione nativa di php "trim" per rimuovere eventuali spazi dalle e-mail
        elseif ($op == 'getemail') {
            $idanagrafica = $get['idanagrafica'];

            if ($idanagrafica != '') {
                $WHERE_IDANAGRAFICA = 'AND idanagrafica = '.$idanagrafica;
            }

            // tutti i referenti per questo cliente
            $q = 'SELECT DISTINCT(email),idanagrafica,nome FROM an_referenti WHERE 1=1 '.$WHERE_IDANAGRAFICA.' ORDER BY idanagrafica';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                if (trim($rs[$i]['email']) != '') {
                    echo html_entity_decode($rs[$i]['nome'].' &lt;'.trim($rs[$i]['email']).'&gt;');
                    if (($i) < $n) {
                        echo '|';
                    }
                }
            }

            // --

            // tutti gli agenti
            $q = "SELECT DISTINCT(email),ragione_sociale,an_anagrafiche.idanagrafica FROM an_anagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE idtipoanagrafica=(SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Agente') ORDER BY idanagrafica";
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                if (trim($rs[$i]['email']) != '') {
                    echo html_entity_decode($rs[$i]['nome'].' &lt;'.trim($rs[$i]['email']).'&gt;');
                    if (($i) < $n) {
                        echo '|';
                    }
                }
            }

            // --

            // email azienda di questo cliente
            $q = 'SELECT DISTINCT(email),ragione_sociale,idanagrafica FROM an_anagrafiche WHERE 1=1 '.$WHERE_IDANAGRAFICA.' ORDER BY idanagrafica';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                if (trim($rs[$i]['email']) != '') {
                    echo html_entity_decode($rs[$i]['ragione_sociale'].' &lt;'.trim($rs[$i]['email']).'&gt;');
                    if (($i + 1) < $n) {
                        echo '|';
                    }
                }
            }
        }

        // Elenco sedi
        elseif ($op == 'get_sedi') {
            $idanagrafica = $get['idanagrafica'];
            $q = "SELECT id, CONCAT_WS( ' - ', nomesede, citta ) AS descrizione FROM an_sedi WHERE idanagrafica='".$idanagrafica."' ".Modules::getAdditionalsQuery('Anagrafiche').' ORDER BY id';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);

            for ($i = 0; $i < $n; ++$i) {
                echo html_entity_decode($rs[$i]['id'].':'.$rs[$i]['descrizione']);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }

        // Elenco sedi con <option>
        elseif ($op == 'get_sedi_select') {
            $idanagrafica = $get['idanagrafica'];
            $q = "SELECT id, CONCAT_WS( ' - ', nomesede, citta ) AS descrizione FROM an_sedi WHERE idanagrafica='".$idanagrafica."' ".Modules::getAdditionalsQuery('Anagrafiche').' ORDER BY id';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);

            echo "<option value=\"-1\">- Nessuna -</option>\n";
            echo "<option value=\"0\">Sede legale</option>\n";

            for ($i = 0; $i < $n; ++$i) {
                echo '<option value="'.$rs[$i]['id'].'">'.$rs[$i]['descrizione']."</option>\n";
            }
        } elseif ($op == 'get_default_value') {
            $idanagrafica = $get['idanagrafica'];
            $q = "SELECT idtipointervento_default FROM an_anagrafiche WHERE idanagrafica='".$idanagrafica."' ".Modules::getAdditionalsQuery('Anagrafiche').'';
            $rs = $dbo->fetchArray($q);

            echo html_entity_decode($rs[0]['idtipointervento_default'], ENT_QUOTES);
        }

        break;

    case 'Articoli':
        // Elenco categorie
        if ($op == 'getcategorie') {
            $q = 'SELECT DISTINCT(categoria) FROM mg_articoli WHERE 1=1 '.Modules::getAdditionalsQuery('Magazzino').' ORDER BY categoria';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                echo html_entity_decode($rs[$i]['categoria']);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }

        // Elenco subcategorie
        elseif ($op == 'getsubcategorie') {
            $q = 'SELECT DISTINCT(subcategoria) FROM mg_articoli WHERE 1=1 '.Modules::getAdditionalsQuery('Magazzino').' ORDER BY subcategoria';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                echo html_entity_decode($rs[$i]['subcategoria']);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }

        // Elenco descrizione
        elseif ($op == 'getdescrizione') {
            $q = 'SELECT DISTINCT(descrizione) FROM mg_articoli WHERE 1=1 '.Modules::getAdditionalsQuery('Magazzino').' ORDER BY descrizione';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                echo html_entity_decode($rs[$i]['descrizione']);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }

        // Elenco codici
        elseif ($op == 'getcodice') {
            $q = 'SELECT DISTINCT(codice) FROM mg_articoli WHERE 1=1 '.Modules::getAdditionalsQuery('Magazzino').' ORDER BY codice';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                echo html_entity_decode($rs[$i]['codice']);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }

        // Elenco lotti in base all'articolo
        elseif ($op == 'getlotti') {
            $idarticolo = $get['idarticolo'];
            $q = 'SELECT DISTINCT(lotto) FROM mg_prodotti WHERE id_articolo="'.$idarticolo.'" '.Modules::getAdditionalsQuery('Magazzino').' ORDER BY lotto ASC';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);

            echo "<option value=''>- Seleziona un lotto -</option>\n";

            for ($i = 0; $i < $n; ++$i) {
                $dir = 'entrata';

                $qs = 'SELECT COUNT(serial) AS num_seriali FROM mg_prodotti WHERE id_articolo='.prepare($idarticolo).' AND lotto='.prepare($rs[$i]['lotto']).' '.
                'AND (serial NOT IN(SELECT serial FROM co_righe_documenti INNER JOIN co_documenti ON co_righe_documenti.iddocumento =  co_documenti.id INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id   WHERE lotto='.prepare($rs[$i]['lotto']).' AND serial=mg_prodotti.serial AND dir = '.prepare($dir).') '.
                'AND  serial NOT IN(SELECT serial FROM or_righe_ordini INNER JOIN or_ordini ON or_righe_ordini.idordine =  or_ordini.id INNER JOIN or_tipiordine ON or_ordini.idtipoordine = or_tipiordine.id WHERE lotto='.prepare($rs[$i]['lotto']).' AND serial=mg_prodotti.serial AND dir = '.prepare($dir).') '.
                'AND  serial NOT IN(SELECT serial FROM dt_righe_ddt INNER JOIN dt_ddt ON dt_righe_ddt.idddt =  dt_ddt.id INNER JOIN dt_tipiddt ON dt_ddt.idtipoddt = dt_tipiddt.id  WHERE lotto='.prepare($rs[$i]['lotto']).' AND serial=mg_prodotti.serial AND dir = '.prepare($dir).') '.
                'AND  serial NOT IN(SELECT serial FROM mg_articoli_interventi WHERE lotto='.prepare($rs[$i]['lotto']).' AND serial=mg_prodotti.serial) ) '.Modules::getAdditionalsQuery('Magazzino').' ORDER BY serial ASC';

                $rsn = $dbo->fetchArray($qs);

                echo '<option value="'.htmlentities($rs[$i]['lotto']).'">'.htmlentities($rs[$i]['lotto']).' ('.htmlentities($rsn[0]['num_seriali']).") </option>\n";

                // echo '<option value="'.htmlentities($rs[$i]['lotto']).'">'.htmlentities($rs[$i]['lotto'])."</option>\n";
            }
        }

        // Elenco lotti in base all'articolo e lotto
        elseif ($op == 'getserial') {
            $idarticolo = $get['idarticolo'];
            $lotto = $get['lotto'];

            if (($get['serial_start'] != '') and ($get['serial_end'] != '')) {
                $serial_start = $get['serial_start'];
                $serial_end = $get['serial_end'];
                $additional_where_serial = ' AND CAST(serial AS UNSIGNED) >= CAST('.prepare($$serial_start).' AS UNSIGNED) AND CAST(serial AS UNSIGNED) <= CAST('.prepare($serial_end).' AS UNSIGNED) ';
            } elseif ($get['serial_start'] != '') {
                $serial_start = $get['serial_start'];
                $additional_where_serial = ' AND CAST(serial AS UNSIGNED) > CAST('.prepare($$serial_start).' AS UNSIGNED)';
            } elseif ($get['serial_end'] != '') {
                $serial_end = $get['serial_end'];
                $additional_where_serial = ' AND CAST(serial AS UNSIGNED) < CAST('.prepare($serial_end).' AS UNSIGNED)';
            } else {
                $additional_where_serial = '';
            }

            if ($dir == 'entrata') {
                $q = 'SELECT DISTINCT(serial) FROM mg_prodotti WHERE id_articolo='.prepare($idarticolo).' AND lotto='.prepare($lotto).
                ' AND (serial NOT IN(SELECT serial FROM co_righe_documenti INNER JOIN co_documenti ON co_righe_documenti.iddocumento =  co_documenti.id INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id   WHERE lotto='.prepare($lotto).' AND serial=mg_prodotti.serial AND dir = '.prepare($dir).')'.
                ' AND serial NOT IN(SELECT serial FROM or_righe_ordini INNER JOIN or_ordini ON or_righe_ordini.idordine =  or_ordini.id INNER JOIN or_tipiordine ON or_ordini.idtipoordine = or_tipiordine.id WHERE lotto='.prepare($lotto).' AND serial=mg_prodotti.serial AND dir = '.prepare($dir).') '.
                ' AND  serial NOT IN(SELECT serial FROM dt_righe_ddt INNER JOIN dt_ddt ON dt_righe_ddt.idddt =  dt_ddt.id INNER JOIN dt_tipiddt ON dt_ddt.idtipoddt = dt_tipiddt.id  WHERE lotto='.prepare($lotto).' AND serial=mg_prodotti.serial AND dir = '.prepare($dir).') '.
                'AND  serial NOT IN(SELECT serial FROM mg_articoli_interventi WHERE lotto='.prepare($lotto).' AND serial=mg_prodotti.serial) '.$additional_where_serial.' ) '.
                Modules::getAdditionalsQuery('Magazzino').' ORDER BY serial ASC';
                $rs = $dbo->fetchArray($q);
                $n = sizeof($rs);
            } else {
                $q = 'SELECT DISTINCT(serial) FROM mg_prodotti WHERE id_articolo='.prepare($idarticolo).' AND lotto='.prepare($lotto).' AND (serial NOT IN(SELECT serial FROM co_righe_documenti   WHERE lotto='.prepare($lotto).' AND serial=mg_prodotti.serial ) AND serial NOT IN(SELECT serial FROM or_righe_ordini WHERE lotto='.prepare($lotto).' AND serial=mg_prodotti.serial)    AND  serial NOT IN(SELECT serial FROM dt_righe_ddt WHERE lotto='.prepare($lotto).' AND serial=mg_prodotti.serial)   AND   serial NOT IN(SELECT serial FROM mg_articoli_interventi WHERE lotto='.prepare($lotto).' AND serial=mg_prodotti.serial) '.$additional_where_serial.' ) '.Modules::getAdditionalsQuery('Magazzino').' ORDER BY serial ASC';
                $rs = $dbo->fetchArray($q);
                $n = sizeof($rs);
            }

            if (($get['serial_start'] != '') or ($get['serial_end'] != '')) {
            } elseif ($get['lotto'] == '') {
                echo "<option value=''>- Seleziona prima un lotto -</option>\n";
            } else {
                echo "<option value=''>- Seleziona un serial number -</option>\n";
            }

            for ($i = 0; $i < $n; ++$i) {
                echo '<option value="'.htmlentities($rs[$i]['serial']).'">'.htmlentities($rs[$i]['serial'])."</option>\n";
            }
        }

        // Elenco lotti in base all'articolo, lotto e serial
        elseif ($op == 'getaltro') {
            $idarticolo = $get['idarticolo'];
            $lotto = $get['lotto'];
            $serial = $get['serial'];
            $q = 'SELECT DISTINCT(altro) FROM mg_prodotti WHERE id_articolo="'.$idarticolo.'" AND lotto="'.$lotto.'" AND serial="'.$serial.'"    AND    (altro NOT IN(SELECT altro FROM co_righe_documenti WHERE lotto="'.$lotto.'" AND serial="'.$serial.'" AND altro=mg_prodotti.altro)    AND    altro NOT IN(SELECT altro FROM or_righe_ordini WHERE lotto="'.$lotto.'" AND serial="'.$serial.'" AND altro=mg_prodotti.altro)    AND    altro NOT IN(SELECT altro FROM dt_righe_ddt WHERE lotto="'.$lotto.'" AND serial="'.$serial.'" AND altro=mg_prodotti.altro)   AND    altro NOT IN(SELECT altro FROM mg_articoli_interventi WHERE lotto="'.$lotto.'" AND serial="'.$serial.'" AND altro=mg_prodotti.altro)) '.Modules::getAdditionalsQuery('Magazzino').' ORDER BY altro ASC';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);

            echo "<option value=''>- Seleziona un altro codice -</option>\n";

            for ($i = 0; $i < $n; ++$i) {
                echo '<option value="'.htmlentities($rs[$i]['altro']).'">'.htmlentities($rs[$i]['altro'])."</option>\n";
            }
        }

        // Legge gli ultimi prezzi di vendita di un determinato cliente e un determinato articolo e li visualizza per suggerire il prezzo di vendita
        elseif ($op == 'getprezzi') {
            $idarticolo = $get['idarticolo'];
            $idanagrafica = $get['idanagrafica'];
            $ids = ['""'];

            echo '<small>';
            if (!empty($idarticolo)) {
                // Ultime 5 vendite al cliente
                $fatture = $dbo->fetchArray('SELECT iddocumento, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM co_documenti WHERE id=iddocumento) AS n_fattura, (SELECT numero_esterno FROM co_documenti WHERE id=iddocumento) AS n2_fattura, (SELECT data FROM co_documenti WHERE id=iddocumento) AS data_fattura FROM co_righe_documenti WHERE idarticolo="'.$idarticolo."\" AND iddocumento IN(SELECT id FROM co_documenti WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir='entrata') AND idanagrafica=\"".$idanagrafica.'") LIMIT 0,5');

                if (sizeof($fatture) > 0) {
                    echo "<br/><table cellspacing='0' class='table-striped' >\n";
                    echo "<tr><th width='150'>Documento</th>\n";
                    echo "<th width='50'>Data</th>\n";
                    echo "<th width='80' class='text-right' >Totale</th></tr>\n";

                    for ($i = 0; $i < sizeof($fatture); ++$i) {
                        ($fatture[$i]['n2_fattura'] != '') ? $n_fattura = $fatture[$i]['n2_fattura'] : $n_fattura = $fatture[$i]['n_fattura'];

                        $id_module = Modules::getModule('Fatture di vendita')['id'];
                        echo "<tr><td class='first_cell text-left'><a href='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$fatture[$i]['iddocumento']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">Fattura num. ".$n_fattura."</a></td>\n";

                        echo "<td class='table_cell text-left'>".Translator::dateToLocale($fatture[$i]['data_fattura'])."</td>\n";
                        echo "<td class='table_cell text-right'>".Translator::numberToLocale($fatture[$i]['costo_unitario'])." &euro;</td></tr>\n";
                        array_push($ids, '"'.$fatture[$i]['iddocumento'].'"');
                    }
                    echo "</table>\n";
                } else {
                    echo '<br/>'.tr('Nessuna vendita di questo articolo al cliente selezionato')."...<br/>\n";
                }
            }
            echo '</small>';
        }

        // Legge gli ultimi prezzi di vendita di un determinato articolo e li visualizza per suggerire il prezzo di vendita
        elseif ($op == 'getprezzivendita') {
            $idarticolo = $get['idarticolo'];

            echo '<small>';
            // Ultime 5 vendite totali
            $fatture = $dbo->fetchArray("SELECT DISTINCT iddocumento, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM co_documenti WHERE id=iddocumento) AS n_fattura, (SELECT numero_esterno FROM co_documenti WHERE id=iddocumento) AS n2_fattura, (SELECT data FROM co_documenti WHERE id=iddocumento) AS data_fattura FROM co_righe_documenti WHERE idarticolo='".$idarticolo."' AND iddocumento IN(SELECT id FROM co_documenti WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir='entrata') ) ORDER BY data_fattura DESC, n_fattura DESC LIMIT 0,5");

            if (sizeof($fatture) > 0) {
                echo "<br/><table cellspacing='0' class='table-striped' >\n";
                echo "<tr><th width='150'>Documento</th>\n";
                echo "<th width='50'>Data</th>\n";
                echo "<th width='80' class='text-right' >Totale</th></tr>\n";

                for ($i = 0; $i < sizeof($fatture); ++$i) {
                    ($fatture[$i]['n2_fattura'] != '') ? $n_fattura = $fatture[$i]['n2_fattura'] : $n_fattura = $fatture[$i]['n_fattura'];

                    $id_module = Modules::getModule('Fatture di vendita')['id'];
                    echo "<tr><td class='first_cell text-left'><a href='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$fatture[$i]['iddocumento']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">Fattura num. ".$n_fattura."</a></td>\n";

                    echo "<td class='table_cell text-left'>".Translator::dateToLocale($fatture[$i]['data_fattura'])."</td>\n";
                    echo "<td class='table_cell text-right'>".Translator::numberToLocale($fatture[$i]['costo_unitario'])." &euro;</td></tr>\n";
                }
                echo "</table>\n";
            } else {
                echo '<br/>'.tr('Questo articolo non è mai stato venduto')."...<br/>\n";
            }
            echo '</small>';
            echo '<br/>';
        }

        // Legge gli ultimi prezzi di vendita di un determinato articolo e li visualizza per suggerire il prezzo di vendita
        elseif ($op == 'getprezziacquisto') {
            $idarticolo = $get['idarticolo'];

            echo '<small>';
            // Ultime 5 vendite totali
            $fatture = $dbo->fetchArray("SELECT DISTINCT iddocumento, (subtotale-sconto)/qta AS costo_unitario, (SELECT numero FROM co_documenti WHERE id=iddocumento) AS n_fattura, (SELECT numero_esterno FROM co_documenti WHERE id=iddocumento) AS n2_fattura, (SELECT data FROM co_documenti WHERE id=iddocumento) AS data_fattura FROM co_righe_documenti WHERE idarticolo='".$idarticolo."' AND iddocumento IN(SELECT id FROM co_documenti WHERE idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir='uscita') ) ORDER BY data_fattura DESC, n_fattura DESC LIMIT 0,5");

            if (sizeof($fatture) > 0) {
                echo "<br/><table cellspacing='0' class='table-striped' >\n";
                echo "<tr><th width='150'>Documento</th>\n";
                echo "<th width='50'>Data</th>\n";
                echo "<th width='80' class='text-right'>Totale</th></tr>\n";

                for ($i = 0; $i < sizeof($fatture); ++$i) {
                    ($fatture[$i]['n2_fattura'] != '') ? $n_fattura = $fatture[$i]['n2_fattura'] : $n_fattura = $fatture[$i]['n_fattura'];

                    $id_module = Modules::getModule('Fatture di vendita')['id'];
                    echo "<tr><td class='first_cell text-left'><a href='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$fatture[$i]['iddocumento']."'  target=\"_blank\" title=\"Apri il documento su una nuova finestra\">Fattura num. ".$n_fattura."</a></td>\n";

                    echo "<td class='table_cell text-left'>".Translator::dateToLocale($fatture[$i]['data_fattura'])."</td>\n";
                    echo "<td class='table_cell text-right'>".Translator::numberToLocale($fatture[$i]['costo_unitario'])." &euro;</td></tr>\n";
                }
                echo "</table>\n";
            } else {
                echo '<br/>'.tr('Questo articolo non è mai stato acquistato')."...<br/>\n";
            }
            echo '</small>';
            echo '<br/>';
        }
        break;

    case 'Interventi':
        // Elenco nomi
        if ($op == 'get_ragione_sociale') {
            $ragione_sociale = $get['ragione_sociale'];
            $q = "SELECT ragione_sociale FROM an_anagrafiche WHERE ragione_sociale LIKE '%$ragione_sociale%' ".Modules::getAdditionalsQuery('Anagrafiche').' ORDER BY ragione_sociale';
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                echo htmlspecialchars_decode($rs[$i]['ragione_sociale'], ENT_QUOTES);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }
        break;

    case 'Preventivi':
        // Elenco nomi preventivi
        if ($op == 'get_select_preventivi') {
            $idanagrafica = $get['idanagrafica'];
            $q = "SELECT co_preventivi.id AS idpreventivo, an_anagrafiche.idanagrafica, nome, idtipointervento FROM co_preventivi INNER JOIN an_anagrafiche ON co_preventivi.idanagrafica=an_anagrafiche.idanagrafica WHERE an_anagrafiche.idanagrafica='$idanagrafica' AND idstato NOT IN (SELECT `id` FROM co_statipreventivi WHERE descrizione='Bozza' OR descrizione='Rifiutato' OR descrizione='Pagato') ".Modules::getAdditionalsQuery('Preventivi');
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                echo html_entity_decode($rs[$i]['idpreventivo'].':'.$rs[$i]['nome'].':'.$rs[$i]['idtipointervento']);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }
        break;

    case 'Contratti':
        // Elenco nomi preventivi
        if ($op == 'get_select_contratti') {
            $idanagrafica = $get['idanagrafica'];
            $q = "SELECT co_contratti.id AS idcontratto, an_anagrafiche.idanagrafica, nome FROM co_contratti INNER JOIN an_anagrafiche ON co_contratti.idanagrafica=an_anagrafiche.idanagrafica WHERE an_anagrafiche.idanagrafica='$idanagrafica' AND idstato NOT IN (SELECT `id` FROM co_staticontratti WHERE fatturabile = 1) ".Modules::getAdditionalsQuery('Contratti');
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            for ($i = 0; $i < $n; ++$i) {
                echo html_entity_decode($rs[$i]['idcontratto'].':'.$rs[$i]['nome']);
                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }
        break;

    case 'Fatture':
        // Elenco fatture non pagate per anagrafica
        if ($op == 'get_fatture') {
            $idanagrafica = $get['idanagrafica'];
            $q = "SELECT data, co_documenti.id AS iddocumento, co_tipidocumento.descrizione AS tipo_doc, dir FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.idanagrafica='$idanagrafica' AND (idstatodocumento=(SELECT `id` FROM co_statidocumento WHERE descrizione='Emessa') OR (SELECT da_pagare-pagato AS differenza FROM co_scadenziario WHERE NOT iddocumento=co_documenti.id=0) )";
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            if ($n > 0) {
                echo "<select name='iddocumento' id='iddocumento' class='inputtext'>\n";
                for ($i = 0; $i < $n; ++$i) {
                    echo "<option value='".$rs[$i]['iddocumento']."' dir=\"".$rs[$i]['dir'].'">'.html_entity_decode($rs[$i]['tipo_doc']).' del '.Translator::dateToLocale($rs[$i]['data'])."</option>\n";
                }
                echo "</select>\n";
            } else {
                echo tr('Nessuna fattura trovata').'...';
            }
        }

        // Elenco causali prima nota
        elseif ($op == 'get_causali') {
            $descrizione = $get['descrizione'];
            $q = 'SELECT DISTINCT descrizione FROM co_movimenti WHERE descrizione LIKE "%'.$descrizione."%\" AND (iddocumento='' OR iddocumento IS NULL) AND primanota=1 ".Modules::getAdditionalsQuery('Prima nota');
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            if ($n > 0) {
                for ($i = 0; $i < $n; ++$i) {
                    echo $rs[$i]['descrizione'];
                    if (($i + 1) < $n) {
                        echo '|';
                    }
                }
            }
        }
        break;

    case 'Ddt':
        // Elenco ddt del fornitore scelto
        if ($op == 'get_ddt') {
            $idfornitore = $get['idfornitore'];
            $q = "SELECT data, dt_ddt.id AS idddt, numero, dt_tipiddt.descrizione AS tipo_doc, dir FROM dt_ddt INNER JOIN dt_tipiddt ON dt_ddt.idtipoddt=dt_tipiddt.id WHERE dt_ddt.idanagrafica='$idfornitore'";
            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);
            if ($n > 0) {
                echo "<select name='idddt' id='idddt' class='inputtext'>\n";
                echo "<option value=''>- Seleziona un ddt -</option>\n";
                for ($i = 0; $i < $n; ++$i) {
                    echo "<option value='".$rs[$i]['idddt']."'>".html_entity_decode($rs[$i]['tipo_doc']).' '.$rs[$i]['numero'].' del '.Translator::dateToLocale($rs[$i]['data'])."</option>\n";
                }
                echo "</select>\n";
            } else {
                echo tr('Nessun ddt trovato')."...\n";
            }
        }
        break;

    case 'MyImpianti':
        // Elenco ddt del fornitore scelto
        if ($op == 'get_impianti') {
            $idanagrafica = $get['idanagrafica'];
            $idsede = $get['idsede'];

            if ($idsede != '' && $idsede != '-1' && $idsede != 'undefined') {
                $q = 'SELECT *, (SELECT nomesede FROM an_sedi WHERE id=my_impianti.idsede) AS nomesede FROM my_impianti WHERE idanagrafica="'.$idanagrafica.'" AND idsede="'.$idsede.'" ORDER BY idsede';
            } else {
                $q = 'SELECT *, (SELECT nomesede FROM an_sedi WHERE id=my_impianti.idsede) AS nomesede FROM my_impianti WHERE idanagrafica="'.$idanagrafica.'" ORDER BY idsede';
            }

            $rs = $dbo->fetchArray($q);
            $n = sizeof($rs);

            for ($i = 0; $i < $n; ++$i) {
                echo $rs[$i]['id'].':'.$rs[$i]['matricola'].' - '.$rs[$i]['nome']."\n";

                if (($i + 1) < $n) {
                    echo '|';
                }
            }
        }
        break;
}

/*
    == Super search ==
    Ricerca di un termine su tutti i moduli.
    Il risultato è in json
*/
if ($op == 'supersearch') {
    $term = $get['term'];
    $term = str_replace('/', '\\/', $term);
    $i = 0;

    if (strlen($term) < 2) {
        echo 'null';
        exit;
    }

    /*
        Anagrafiche
    */
    if (Modules::getPermission('Anagrafiche') != '-') {
        $campi = ['codice', 'ragione_sociale', 'piva', 'codice_fiscale', 'indirizzo', 'indirizzo2', 'citta', 'cap', 'provincia', 'telefono', 'fax', 'cellulare', 'email', 'sitoweb', 'note', 'codicerea', 'settore', 'marche', 'cciaa', 'n_alboartigiani'];
        $campi_text = ['Codice', 'Ragione sociale', 'Partita iva', 'Codice fiscale', 'Indirizzo', 'Indirizzo2', 'Città', 'C.A.P.', 'Provincia', 'Telefono', 'Fax', 'Cellulare', 'Email', 'Sito web', 'Note', 'Codice REA', 'Settore', 'Marche', 'CCIAA', 'Numero di iscrizione albo artigiani'];

        $rs = $dbo->fetchArray("SELECT id FROM zz_modules WHERE name='Anagrafiche'");
        $id_module = $rs[0]['id'];

        $build_query = '';

        for ($c = 0; $c < sizeof($campi); ++$c) {
            $build_query .= ' OR '.$campi[$c].' LIKE "%'.$term.'%" AND deleted = 0';
        }

        $rs = $dbo->fetchArray('SELECT * FROM an_anagrafiche WHERE 1=0 '.$build_query);

        if (sizeof($rs) > 0) {
            // Loop record corrispondenti alla ricerca
            for ($r = 0; $r < sizeof($rs); ++$r) {
                $result[$r + $i]['link'] = $rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['idanagrafica'];
                $result[$r + $i]['title'] = $rs[$r]['ragione_sociale'];
                $result[$r + $i]['category'] = 'Anagrafiche';
                $result[$r + $i]['labels'] = [];

                // Loop campi da evidenziare
                for ($c = 0; $c < sizeof($campi); ++$c) {
                    if (preg_match('/'.$term.'/i', $rs[$r][$campi[$c]])) {
                        $text = $rs[$r][$campi[$c]];

                        // Evidenzio la parola cercata nei valori dei campi
                        preg_match('/'.$term.'/i', $rs[$r][$campi[$c]], $matches);

                        for ($m = 0; $m < sizeof($matches); ++$m) {
                            $text = str_replace($matches[$m], "<span class='highlight'>".$matches[$m].'</span>', $text);
                        }

                        $result[$r + $i]['labels'][] = $campi_text[$c].': '.$text.'<br/>';
                    }
                }
            }

            $i += $r;
        }
    }

    // Ricerca anagrafiche per ragione sociale per potere mostrare gli interventi, fatture,
    // ordini, ecc della persona ricercata
    $idanagrafiche = ['-1'];
    $ragioni_sociali = ['-1'];
    $rs = $dbo->fetchArray('SELECT idanagrafica, ragione_sociale FROM an_anagrafiche WHERE ragione_sociale LIKE "%'.$term.'%"');

    for ($a = 0; $a < sizeof($rs); ++$a) {
        $idanagrafiche[] = $rs[$a]['idanagrafica'];
        $ragioni_sociali[$rs[$a]['idanagrafica']] = $rs[$a]['ragione_sociale'];
    }

    /*
        Referenti anagrafiche
    */
    if (Modules::getPermission('Anagrafiche') != '-') {
        $campi = ['nome', 'mansione', 'telefono', 'email'];
        $campi_text = ['Nome', 'Mansione', 'Telefono', 'Email'];

        $build_query = '';

        for ($c = 0; $c < sizeof($campi); ++$c) {
            $build_query .= ' OR '.$campi[$c].' LIKE "%'.$term.'%"';
        }

        $rs = $dbo->fetchArray('SELECT * FROM an_referenti WHERE idanagrafica IN('.implode(',', $idanagrafiche).') '.$build_query);

        if (sizeof($rs) > 0) {
            // Loop record corrispondenti alla ricerca
            for ($r = 0; $r < sizeof($rs); ++$r) {
                $result[$r + $i]['link'] = $rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['idanagrafica'].'#tabs-2';
                $result[$r + $i]['title'] = $rs[$r]['nome'];
                $result[$r + $i]['category'] = 'Referenti';
                $result[$r + $i]['labels'] = [];

                // Loop campi da evidenziare
                for ($c = 0; $c < sizeof($campi); ++$c) {
                    if (preg_match('/'.$term.'/i', $rs[$r][$campi[$c]])) {
                        $text = $rs[$r][$campi[$c]];

                        // Evidenzio la parola cercata nei valori dei campi
                        preg_match('/'.$term.'/i', $rs[$r + $i][$campi[$c]], $matches);

                        for ($m = 0; $m < sizeof($matches); ++$m) {
                            $text = str_replace($matches[$m], "<span class='highlight'>".$matches[$m].'</span>', $text);
                        }

                        $result[$r + $i]['labels'][] = $campi_text[$c].': '.$text.'<br/>';
                    }
                }

                // Aggiunta nome anagrafica come ultimo campo
                if (sizeof($ragioni_sociali) > 1) {
                    $result[$r + $i]['labels'][] = 'Anagrafica: '.$ragioni_sociali[$rs[$r]['idanagrafica']].'<br/>';
                }
            }

            $i += $r;
        }
    }

    /*
        Interventi
    */
    if (Modules::getPermission('Interventi') != '-') {
        $campi = ['codice', '(SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id)', 'data_richiesta', 'info_sede', 'richiesta', 'descrizione', 'informazioniaggiuntive'];
        $campi_text = ['Codice intervento', 'Data intervento', 'Data richiesta intervento', 'Sede intervento', 'Richiesta', 'Descrizione', 'Informazioni aggiuntive'];

        $id_module = Modules::getModule('Interventi')['id'];

        $build_query = '';

        for ($c = 0; $c < sizeof($campi); ++$c) {
            $build_query .= ' OR '.$campi[$c].' LIKE "%'.$term.'%"';
        }

        $build_query .= Modules::getAdditionalsQuery('Interventi');

        $rs = $dbo->fetchArray('SELECT *, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data FROM in_interventi WHERE idanagrafica IN('.implode(',', $idanagrafiche).') '.$build_query);

        if (sizeof($rs) > 0) {
            // Loop record corrispondenti alla ricerca
            for ($r = 0; $r < sizeof($rs); ++$r) {
                $result[$r + $i]['link'] = $rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['id'];
                $result[$r + $i]['title'] = 'Intervento '.$rs[$r]['codice'].' del '.Translator::dateToLocale($rs[$r]['data']);
                $result[$r + $i]['category'] = 'Interventi';
                $result[$r + $i]['labels'] = [];

                // Loop campi da evidenziare
                for ($c = 0; $c < sizeof($campi); ++$c) {
                    if (preg_match('/'.$term.'/i', $rs[$r][$campi[$c]])) {
                        $text = $rs[$r][$campi[$c]];

                        // Evidenzio la parola cercata nei valori dei campi
                        preg_match('/'.$term.'/i', $rs[$r][$campi[$c]], $matches);

                        for ($m = 0; $m < sizeof($matches); ++$m) {
                            $text = str_replace($matches[$m], "<span class='highlight'>".$matches[$m].'</span>', $text);
                        }

                        $result[$r + $i]['labels'][] = $campi_text[$c].': '.$text.'<br/>';
                    }
                }

                // Aggiunta nome anagrafica come ultimo campo
                if (sizeof($ragioni_sociali) > 1) {
                    $result[$r + $i]['labels'][] = 'Anagrafica: '.$ragioni_sociali[$rs[$r]['idanagrafica']].'<br/>';
                }
            }

            $i += $r;
        }
    }

    /*
        Preventivi
    */
    if (Modules::getPermission('Contabilita') != '-') {
        $campi = ['numero', 'nome', 'descrizione'];
        $campi_text = ['Codice preventivo', 'Nome', 'Descrizione'];

        $rs = $dbo->fetchArray("SELECT id FROM zz_modules WHERE name='Preventivi'");
        $id_module = $rs[0]['id'];

        $build_query = '';

        for ($c = 0; $c < sizeof($campi); ++$c) {
            $build_query .= ' OR '.$campi[$c].' LIKE "%'.$term.'%"';
        }

        $rs = $dbo->fetchArray('SELECT *, co_preventivi.id AS idpreventivo FROM co_preventivi WHERE idanagrafica IN('.implode(',', $idanagrafiche).') '.$build_query);

        if (sizeof($rs) > 0) {
            // Loop record corrispondenti alla ricerca
            for ($r = 0; $r < sizeof($rs); ++$r) {
                $result[$r + $i]['link'] = $rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['idpreventivo'];
                $result[$r + $i]['title'] = 'Preventivo '.$rs[$r]['numero'].(($rs[$r]['data_accettazione'] == '0000-00-00') ? ' del '.Translator::dateToLocale($rs[$r]['data_accettazione']) : '');
                $result[$r + $i]['category'] = 'Preventivi';
                $result[$r + $i]['labels'] = [];

                // Loop campi da evidenziare
                for ($c = 0; $c < sizeof($campi); ++$c) {
                    if (preg_match('/'.$term.'/i', $rs[$r][$campi[$c]])) {
                        $text = $rs[$r][$campi[$c]];

                        // Evidenzio la parola cercata nei valori dei campi
                        preg_match('/'.$term.'/i', $rs[$r][$campi[$c]], $matches);

                        for ($m = 0; $m < sizeof($matches); ++$m) {
                            $text = str_replace($matches[$m], "<span class='highlight'>".$matches[$m].'</span>', $text);
                        }

                        $result[$r + $i]['labels'][] = $campi_text[$c].': '.$text.'<br/>';
                    }
                }

                // Aggiunta nome anagrafica come ultimo campo
                if (sizeof($ragioni_sociali) > 1) {
                    $result[$r + $i]['labels'][] = 'Anagrafica: '.$ragioni_sociali[$rs[$r]['idanagrafica']].'<br/>';
                }
            }

            $i += $r;
        }
    }

    /*
        Fatture
    */
    if (Modules::getPermission('Contabilita') != '-') {
        $campi = ['numero', 'numero_esterno', 'data', 'note', 'note_aggiuntive', 'buono_ordine'];
        $campi_text = ['Numero', 'Numero secondario', 'Data', 'Note', 'Note aggiuntive', 'Buono d\'ordine'];

        $build_query = '';

        for ($c = 0; $c < sizeof($campi); ++$c) {
            $build_query .= ' OR '.$campi[$c].' LIKE "%'.$term.'%"';
        }

        $rs = $dbo->fetchArray('SELECT *, co_documenti.id AS iddocumento FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE idanagrafica IN('.implode(',', $idanagrafiche).') '.$build_query);

        if (sizeof($rs) > 0) {
            // Loop record corrispondenti alla ricerca
            for ($r = 0; $r < sizeof($rs); ++$r) {
                if ($rs[$r]['numero_esterno'] == '') {
                    $numero = $rs[$r]['numero'];
                } else {
                    $numero = $rs[$r]['numero_esterno'];
                }

                // Controllo se si tratta di una fattura di acquisto o di vendita e seleziono il modulo opportuno
                if ($rs[$r]['dir'] == 'uscita') {
                    $rsm = $dbo->fetchArray("SELECT id FROM zz_modules WHERE name = 'Fatture di acquisto'");
                    $id_module = $rsm[0]['id'];
                } else {
                    $rsm = $dbo->fetchArray("SELECT id FROM zz_modules WHERE name = 'Fatture di vendita'");
                    $id_module = $rsm[0]['id'];
                }

                $result[$r + $i]['link'] = $rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['iddocumento'];
                $result[$r + $i]['title'] = $rs[$r]['descrizione'].' num. '.$numero.' del '.Translator::dateToLocale($rs[$r]['data']);
                $result[$r + $i]['category'] = $rs[$r]['descrizione'];
                $result[$r + $i]['labels'] = [];

                // Loop campi da evidenziare
                for ($c = 0; $c < sizeof($campi); ++$c) {
                    if (preg_match('/'.$term.'/i', $rs[$r][$campi[$c]])) {
                        $text = $rs[$r][$campi[$c]];

                        // Evidenzio la parola cercata nei valori dei campi
                        preg_match('/'.$term.'/i', $rs[$r][$campi[$c]], $matches);

                        for ($m = 0; $m < sizeof($matches); ++$m) {
                            $text = str_replace($matches[$m], "<span class='highlight'>".$matches[$m].'</span>', $text);
                        }

                        $result[$r + $i]['labels'][] = $campi_text[$c].': '.$text.'<br/>';
                    }
                }

                // Aggiunta nome anagrafica come ultimo campo
                if (sizeof($ragioni_sociali) > 1) {
                    $result[$r + $i]['labels'][] = 'Anagrafica: '.$ragioni_sociali[$rs[$r]['idanagrafica']].'<br/>';
                }
            }

            $i += $r;
        }
    }

    /*
        Righe fatture
    */
    if (Modules::getPermission('Contabilita') != '-') {
        $campi = ['descrizione'];
        $campi_text = ['Riga'];

        $build_query = '';

        for ($c = 0; $c < sizeof($campi); ++$c) {
            $build_query .= ' OR co_righe_documenti.'.$campi[$c].' LIKE "%'.$term.'%"';
        }

        $rs = $dbo->fetchArray('SELECT co_documenti.*, co_documenti.id AS iddocumento, co_tipidocumento.descrizione AS tipodoc, co_tipidocumento.dir, co_righe_documenti.descrizione FROM co_righe_documenti INNER JOIN (co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id) ON co_documenti.id=co_righe_documenti.iddocumento WHERE idanagrafica IN('.implode(',', $idanagrafiche).') '.$build_query);

        if (sizeof($rs) > 0) {
            // Loop record corrispondenti alla ricerca
            for ($r = 0; $r < sizeof($rs); ++$r) {
                if ($rs[$r]['numero_esterno'] == '') {
                    $numero = $rs[$r]['numero'];
                } else {
                    $numero = $rs[$r]['numero_esterno'];
                }

                // Controllo se si tratta di una fattura di acquisto o di vendita e seleziono il modulo opportuno
                if ($rs[$r]['dir'] == 'uscita') {
                    $rsm = $dbo->fetchArray("SELECT id FROM zz_modules WHERE name = 'Fatture di acquisto'");
                    $id_module = $rsm[0]['id'];
                } else {
                    $rsm = $dbo->fetchArray("SELECT id FROM zz_modules WHERE name = 'Fatture di vendita'");
                    $id_module = $rsm[0]['id'];
                }

                $result[$r + $i]['link'] = $rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['iddocumento'];
                $result[$r + $i]['title'] = $rs[$r]['tipodoc'].' num. '.$numero.' del '.Translator::dateToLocale($rs[$r]['data']);
                $result[$r + $i]['category'] = $rs[$r]['tipodoc'];
                $result[$r + $i]['labels'] = [];

                // Loop campi da evidenziare
                for ($c = 0; $c < sizeof($campi); ++$c) {
                    if (preg_match('/'.$term.'/i', $rs[$r][$campi[$c]])) {
                        $text = $rs[$r][$campi[$c]];

                        // Evidenzio la parola cercata nei valori dei campi
                        preg_match('/'.$term.'/i', $rs[$r][$campi[$c]], $matches);

                        for ($m = 0; $m < sizeof($matches); ++$m) {
                            $text = str_replace($matches[$m], "<span class='highlight'>".$matches[$m].'</span>', $text);
                        }

                        $result[$r + $i]['labels'][] = $campi_text[$c].': '.$text.'<br/>';
                    }
                }

                // Aggiunta nome anagrafica come ultimo campo
                if (sizeof($ragioni_sociali) > 1) {
                    $result[$r + $i]['labels'][] = 'Anagrafica: '.$ragioni_sociali[$rs[$r]['idanagrafica']].'<br/>';
                }
            }

            $i += $r;
        }
    }

    /*
        Articoli
    */
    if (Modules::getPermission('Articoli') != '-') {
        $campi = ['codice', 'descrizione', '(SELECT nome FROM mg_categorie WHERE mg_categorie.id =  mg_articoli.id_categoria)', '(SELECT nome FROM mg_categorie WHERE mg_categorie.id =  mg_articoli.id_sottocategoria)', 'note'];
        $campi_text = ['Codice', 'Descrizione', 'Categoria', 'Subcategoria', 'Note'];

        $rs = $dbo->fetchArray("SELECT id FROM zz_modules WHERE name='Articoli'");
        $id_module = $rs[0]['id'];

        $build_query = '';

        for ($c = 0; $c < sizeof($campi); ++$c) {
            $build_query .= ' OR '.$campi[$c].' LIKE "%'.$term.'%"';
        }

        $rs = $dbo->fetchArray('SELECT * FROM mg_articoli WHERE 1=0 '.$build_query);

        if (sizeof($rs) > 0) {
            // Loop record corrispondenti alla ricerca
            for ($r = 0; $r < sizeof($rs); ++$r) {
                $result[$r + $i]['link'] = $rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['id'];
                $result[$r + $i]['title'] = $rs[$r]['codice'].' - '.$rs[$r]['descrizione'];
                $result[$r + $i]['category'] = 'Articoli';
                $result[$r + $i]['labels'] = [];

                // Loop campi da evidenziare
                for ($c = 0; $c < sizeof($campi); ++$c) {
                    if (preg_match('/'.$term.'/i', $rs[$r][$campi[$c]])) {
                        $text = $rs[$r][$campi[$c]];

                        // Evidenzio la parola cercata nei valori dei campi
                        preg_match('/'.$term.'/i', $rs[$r][$campi[$c]], $matches);

                        for ($m = 0; $m < sizeof($matches); ++$m) {
                            $text = str_replace($matches[$m], "<span class='highlight'>".$matches[$m].'</span>', $text);
                        }

                        $result[$r + $i]['labels'][] = $campi_text[$c].': '.$text.'<br/>';
                    }
                }
            }

            $i += $r;
        }
    }

    /*
        Automezzi
    */
    if (Modules::getPermission('Automezzi') != '-') {
        $campi = ['nome', 'descrizione', 'targa'];
        $campi_text = ['Nome', 'Descrizione', 'Targa'];

        $rs = $dbo->fetchArray("SELECT id FROM zz_modules WHERE name='Automezzi'");
        $id_module = $rs[0]['id'];

        $build_query = '';

        for ($c = 0; $c < sizeof($campi); ++$c) {
            $build_query .= ' OR '.$campi[$c].' LIKE "%'.$term.'%"';
        }

        $rs = $dbo->fetchArray('SELECT * FROM dt_automezzi WHERE 1=0 '.$build_query);

        if (sizeof($rs) > 0) {
            // Loop record corrispondenti alla ricerca
            for ($r = 0; $r < sizeof($rs); ++$r) {
                $result[$r + $i]['link'] = $rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['id'];
                $result[$r + $i]['title'] = $rs[$r]['nome'];
                $result[$r + $i]['category'] = 'Automezzi';
                $result[$r + $i]['labels'] = [];

                // Loop campi da evidenziare
                for ($c = 0; $c < sizeof($campi); ++$c) {
                    if (preg_match('/'.$term.'/i', $rs[$r][$campi[$c]])) {
                        $text = $rs[$r][$campi[$c]];

                        // Evidenzio la parola cercata nei valori dei campi
                        preg_match('/'.$term.'/i', $rs[$r][$campi[$c]], $matches);

                        for ($m = 0; $m < sizeof($matches); ++$m) {
                            $text = str_replace($matches[$m], "<span class='highlight'>".$matches[$m].'</span>', $text);
                        }

                        $result[$r + $i]['labels'][] = $campi_text[$c].': '.$text.'<br/>';
                    }
                }
            }

            $i += $r;
        }
    }

    /*
        Ddt
    */
    if (Modules::getPermission('Magazzino') != '-') {
        $campi = ['numero', 'numero_esterno', 'data', 'note'];
        $campi_text = ['Numero', 'Numero secondario', 'Data', 'Note'];

        $build_query = '';

        for ($c = 0; $c < sizeof($campi); ++$c) {
            $build_query .= ' OR '.$campi[$c].' LIKE "%'.$term.'%"';
        }

        $rs = $dbo->fetchArray('SELECT *, dt_ddt.id AS idddt FROM dt_ddt INNER JOIN dt_tipiddt ON dt_ddt.idtipoddt=dt_tipiddt.id WHERE idanagrafica IN('.implode(',', $idanagrafiche).') '.$build_query);

        if (sizeof($rs) > 0) {
            // Loop record corrispondenti alla ricerca
            for ($r = 0; $r < sizeof($rs); ++$r) {
                if ($rs[$r]['numero_esterno'] == '') {
                    $numero = $rs[$r]['numero'];
                } else {
                    $numero = $rs[$r]['numero_esterno'];
                }

                // Controllo se si tratta di un tipo ddt di acquisto o di vendita e seleziono il modulo opportuno
                if ($rs[$r]['dir'] == 'uscita') {
                    $rsm = $dbo->fetchArray("SELECT id FROM zz_modules WHERE name = 'Ddt di acquisto'");
                    $id_module = $rsm[0]['id'];
                } else {
                    $rsm = $dbo->fetchArray("SELECT id FROM zz_modules WHERE name = 'Ddt di vendita'");
                    $id_module = $rsm[0]['id'];
                }

                $result[$r + $i]['link'] = $rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['idddt'];
                $result[$r + $i]['title'] = $rs[$r]['descrizione'].' num. '.$numero.' del '.Translator::dateToLocale($rs[$r]['data']);
                $result[$r + $i]['category'] = $rs[$r]['descrizione'];
                $result[$r + $i]['labels'] = [];

                // Loop campi da evidenziare
                for ($c = 0; $c < sizeof($campi); ++$c) {
                    if (preg_match('/'.$term.'/i', $rs[$r][$campi[$c]])) {
                        $text = $rs[$r][$campi[$c]];

                        // Evidenzio la parola cercata nei valori dei campi
                        preg_match('/'.$term.'/i', $rs[$r][$campi[$c]], $matches);

                        for ($m = 0; $m < sizeof($matches); ++$m) {
                            $text = str_replace($matches[$m], "<span class='highlight'>".$matches[$m].'</span>', $text);
                        }

                        $result[$r + $i]['labels'][] = $campi_text[$c].': '.$text.'<br/>';
                    }
                }

                // Aggiunta nome anagrafica come ultimo campo
                if (sizeof($ragioni_sociali) > 1) {
                    $result[$r + $i]['labels'][] = 'Anagrafica: '.$ragioni_sociali[$rs[$r]['idanagrafica']].'<br/>';
                }
            }

            $i += $r;
        }
    }

    /*
        Righe ddt
    */
    if (Modules::getPermission('Magazzino') != '-') {
        $campi = ['descrizione'];
        $campi_text = ['Riga'];

        $build_query = '';

        for ($c = 0; $c < sizeof($campi); ++$c) {
            $build_query .= ' OR dt_righe_ddt.'.$campi[$c].' LIKE "%'.$term.'%"';
        }

        $rs = $dbo->fetchArray('SELECT dt_ddt.*, dt_ddt.id AS idddt, dt_tipiddt.descrizione AS tipodoc, dt_tipiddt.dir, dt_righe_ddt.descrizione FROM dt_righe_ddt INNER JOIN (dt_ddt INNER JOIN dt_tipiddt ON dt_ddt.idtipoddt=dt_tipiddt.id) ON dt_ddt.id=dt_righe_ddt.idddt WHERE idanagrafica IN('.implode(',', $idanagrafiche).') '.$build_query);

        if (sizeof($rs) > 0) {
            // Loop record corrispondenti alla ricerca
            for ($r = 0; $r < sizeof($rs); ++$r) {
                if ($rs[$r]['numero_esterno'] == '') {
                    $numero = $rs[$r]['numero'];
                } else {
                    $numero = $rs[$r]['numero_esterno'];
                }

                // Controllo se si tratta di un tipo ddt di acquisto o di vendita e seleziono il modulo opportuno
                if ($rs[$r]['dir'] == 'uscita') {
                    $rsm = $dbo->fetchArray("SELECT id FROM zz_modules WHERE name = 'Ddt di acquisto'");
                    $id_module = $rsm[0]['id'];
                } else {
                    $rsm = $dbo->fetchArray("SELECT id FROM zz_modules WHERE name = 'Ddt di vendita'");
                    $id_module = $rsm[0]['id'];
                }

                $result[$r + $i]['link'] = $rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['idddt'];
                // $result[$r+$i]['link']		= $rootdir."/modules/magazzino/ddt/ddt.php?idddt=".$rs[$r]['iddocumento']."&dir=".$rs[$r]['dir'];
                $result[$r + $i]['title'] = $rs[$r]['tipodoc'].' num. '.$numero.' del '.Translator::dateToLocale($rs[$r]['data']);
                $result[$r + $i]['category'] = $rs[$r]['tipodoc'];
                $result[$r + $i]['labels'] = [];

                // Loop campi da evidenziare
                for ($c = 0; $c < sizeof($campi); ++$c) {
                    if (preg_match('/'.$term.'/i', $rs[$r][$campi[$c]])) {
                        $text = $rs[$r][$campi[$c]];

                        // Evidenzio la parola cercata nei valori dei campi
                        preg_match('/'.$term.'/i', $rs[$r][$campi[$c]], $matches);

                        for ($m = 0; $m < sizeof($matches); ++$m) {
                            $text = str_replace($matches[$m], "<span class='highlight'>".$matches[$m].'</span>', $text);
                        }

                        $result[$r + $i]['labels'][] = $campi_text[$c].': '.$text.'<br/>';
                    }
                }

                // Aggiunta nome anagrafica come ultimo campo
                if (sizeof($ragioni_sociali) > 1) {
                    $result[$r + $i]['labels'][] = 'Anagrafica: '.$ragioni_sociali[$rs[$r]['idanagrafica']].'<br/>';
                }
            }

            $i += $r;
        }
    }

    /*
        MyImpianti
    */
    if (Modules::getPermission('MyImpianti') != '-') {
        $campi = ['matricola', 'nome', 'descrizione', 'ubicazione', 'occupante', 'proprietario'];
        $campi_text = ['Matricola', 'Nome', 'Descrizione', 'Ubicazione', 'Occupante', 'Proprietario'];

        $rs = $dbo->fetchArray("SELECT id FROM zz_modules WHERE name='MyImpianti'");
        $id_module = $rs[0]['id'];

        $build_query = '';

        for ($c = 0; $c < sizeof($campi); ++$c) {
            $build_query .= ' OR '.$campi[$c].' LIKE "%'.$term.'%"';
        }

        $build_query .= Modules::getAdditionalsQuery('MyImpianti');

        $rs = $dbo->fetchArray('SELECT * FROM my_impianti WHERE idanagrafica IN('.implode(',', $idanagrafiche).') '.$build_query);

        if (sizeof($rs) > 0) {
            // Loop record corrispondenti alla ricerca
            for ($r = 0; $r < sizeof($rs); ++$r) {
                $result[$r + $i]['link'] = $rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['matricola'];
                $result[$r + $i]['title'] = $rs[$r]['matricola'].' - '.$rs[$r]['nome'];
                $result[$r + $i]['category'] = 'MyImpianti';
                $result[$r + $i]['labels'] = [];

                // Loop campi da evidenziare
                for ($c = 0; $c < sizeof($campi); ++$c) {
                    if (preg_match('/'.$term.'/i', $rs[$r][$campi[$c]])) {
                        $text = $rs[$r][$campi[$c]];

                        // Evidenzio la parola cercata nei valori dei campi
                        preg_match('/'.$term.'/i', $rs[$r][$campi[$c]], $matches);

                        for ($m = 0; $m < sizeof($matches); ++$m) {
                            $text = str_replace($matches[$m], "<span class='highlight'>".$matches[$m].'</span>', $text);
                        }

                        $result[$r + $i]['labels'][] = $campi_text[$c].': '.$text.'<br/>';
                    }
                }

                // Aggiunta nome anagrafica come ultimo campo
                if (sizeof($ragioni_sociali) > 1) {
                    $result[$r + $i]['labels'][] = 'Anagrafica: '.$ragioni_sociali[$rs[$r]['idanagrafica']].'<br/>';
                }
            }

            $i += $r;
        }
    }

    // Contratti
    // Ordini

    $result = (array) $result;
    foreach ($result as $key => $value) {
        $result[$key]['value'] = $key;
    }

    echo json_encode($result);
}
