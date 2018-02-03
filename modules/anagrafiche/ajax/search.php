<?php

include_once __DIR__.'/../../../core.php';

/*
    Anagrafiche
*/
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
        $result = [];

        $result['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['idanagrafica'];
        $result['title'] = $rs[$r]['ragione_sociale'];
        $result['category'] = 'Anagrafiche';
        $result['labels'] = [];

        // Loop campi da evidenziare
        for ($c = 0; $c < sizeof($campi); ++$c) {
            if (preg_match('/'.$term.'/i', $rs[$r][$campi[$c]])) {
                $text = $rs[$r][$campi[$c]];

                // Evidenzio la parola cercata nei valori dei campi
                preg_match('/'.$term.'/i', $rs[$r][$campi[$c]], $matches);

                for ($m = 0; $m < sizeof($matches); ++$m) {
                    $text = str_replace($matches[$m], "<span class='highlight'>".$matches[$m].'</span>', $text);
                }

                $result['labels'][] = $campi_text[$c].': '.$text.'<br/>';
            }
        }

        $results[] = $result;
    }
}

/*
    Referenti anagrafiche
*/
$campi = ['nome', 'mansione', 'telefono', 'email'];
$campi_text = ['Nome', 'Mansione', 'Telefono', 'Email'];

$build_query = '';

for ($c = 0; $c < sizeof($campi); ++$c) {
    $build_query .= ' OR '.$campi[$c].' LIKE "%'.$term.'%"';
}

$rs = $dbo->fetchArray('SELECT * FROM an_referenti WHERE idanagrafica IN('.implode(',', $idanagrafiche).') '.$build_query);

if (sizeof($rs) > 0) {
    $result = [];

    // Loop record corrispondenti alla ricerca
    for ($r = 0; $r < sizeof($rs); ++$r) {
        $result['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$rs[$r]['idanagrafica'].'#tabs-2';
        $result['title'] = $rs[$r]['nome'];
        $result['category'] = 'Referenti';
        $result['labels'] = [];

        // Loop campi da evidenziare
        for ($c = 0; $c < sizeof($campi); ++$c) {
            if (preg_match('/'.$term.'/i', $rs[$r][$campi[$c]])) {
                $text = $rs[$r][$campi[$c]];

                // Evidenzio la parola cercata nei valori dei campi
                preg_match('/'.$term.'/i', $rs[$r][$campi[$c]], $matches);

                for ($m = 0; $m < sizeof($matches); ++$m) {
                    $text = str_replace($matches[$m], "<span class='highlight'>".$matches[$m].'</span>', $text);
                }

                $result['labels'][] = $campi_text[$c].': '.$text.'<br/>';
            }
        }

        // Aggiunta nome anagrafica come ultimo campo
        if (sizeof($ragioni_sociali) > 1) {
            $result['labels'][] = 'Anagrafica: '.$ragioni_sociali[$rs[$r]['idanagrafica']].'<br/>';
        }

        $results[] = $result;
    }
}
