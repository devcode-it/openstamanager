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

    $results = $result;
}
