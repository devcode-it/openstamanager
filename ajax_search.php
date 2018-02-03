<?php

include_once __DIR__.'/core.php';

if (!isset($term)) {
    /*
    == Super search ==
    Ricerca di un termine su tutti i moduli.
    Il risultato è in json
    */

    $term = $get['term'];
    $term = str_replace('/', '\\/', $term);

    $results = AJAX::search($term);

    echo json_encode($results);
}

// Casi particolari
else {
    $i = 0;

    /*
        Interventi
    */
    if (Modules::getPermission('Interventi') != '-') {
        $campi = ['codice', '(SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id)', 'data_richiesta', 'info_sede', 'richiesta', 'descrizione', 'informazioniaggiuntive'];
        $campi_text = ['Codice intervento', 'Data intervento', 'Data richiesta intervento', 'Sede intervento', 'Richiesta', 'Descrizione', 'Informazioni aggiuntive'];

        $id_module = Modules::get('Interventi')['id'];

        $build_query = '';

        for ($c = 0; $c < sizeof($campi); ++$c) {
            $build_query .= ' OR '.$campi[$c].' LIKE "%'.$term.'%"';
        }

        $build_query .= Modules::getAdditionalsQuery('Interventi');

        $rs = $dbo->fetchArray('SELECT *, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data FROM in_interventi WHERE idanagrafica IN('.implode(',', $idanagrafiche).') '.$build_query);

        if (sizeof($rs) > 0) {
            // Loop record corrispondenti alla ricerca
            for ($r = 0; $r < sizeof($rs); ++$r) {
                $result[$r + $i]['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['id'];
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
                $result[$r + $i]['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['idpreventivo'];
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

                $result[$r + $i]['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['iddocumento'];
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

                $result[$r + $i]['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['iddocumento'];
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
                $result[$r + $i]['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['id'];
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
                $result[$r + $i]['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['id'];
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

                $result[$r + $i]['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['idddt'];
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

                $result[$r + $i]['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['idddt'];
                // $result[$r+$i]['link']		= ROOTDIR."/modules/magazzino/ddt/ddt.php?idddt=".$rs[$r]['iddocumento']."&dir=".$rs[$r]['dir'];
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
                $result[$r + $i]['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['matricola'];
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

    $results = $result;
}
