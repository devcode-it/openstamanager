<?php

include_once __DIR__.'/../../../core.php';

/*
    Anagrafiche
*/

$id_module = Modules::get('Anagrafiche')['id'];

$fields = [
    'Codice' => 'codice',
    'Ragione sociale' => 'ragione_sociale',
    'Partita iva' => 'piva',
    'Codice fiscale' => 'codice_fiscale',
    'Indirizzo' => 'indirizzo',
    'Indirizzo2' => 'indirizzo2',
    'Città' => 'citta',
    'C.A.P.' => 'cap',
    'Provincia' => 'provincia',
    'Telefono' => 'telefono',
    'Fax' => 'fax',
    'Cellulare' => 'cellulare',
    'Email' => 'email',
    'Sito web' => 'sitoweb',
    'Note' => 'note',
    'Codice REA' => 'codicerea',
    'Settore' => 'settore',
    'Marche' => 'marche',
    'CCIAA' => 'cciaa',
    'Numero di iscrizione albo artigiani' => 'n_alboartigiani',
];

$query = 'SELECT *, idanagrafica AS id';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM an_anagrafiche WHERE 1=0 ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE "%'.$term.'%"';
}

$query .= Modules::getAdditionalsQuery('Anagrafiche');

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $result['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$r['id'];
    $result['title'] = $r['ragione_sociale'];
    $result['title'] .= !empty($r['deleted_at']) ? ' <small class="text-danger"><em>('.tr('Eliminato').')</em></small>': '';
    $result['category'] = 'Anagrafiche';

    // Campi da evidenziare
    $result['labels'] = [];
    foreach ($fields as $name => $value) {
        if (str_contains($r[$name], $term)) {
            $text = str_replace($term, "<span class='highlight'>".$term.'</span>', $r[$name]);

            $result['labels'][] = $name.': '.$text.'<br/>';
        }
    }

    $results[] = $result;
}

// Referenti anagrafiche
$fields = [
    'Nome' => 'nome',
    'Mansione' => 'mansione',
    'Telefono' => 'telefono',
    'Email' => 'email',
];

$query = 'SELECT *, idanagrafica as id';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM an_referenti WHERE idanagrafica IN('.implode(',', $idanagrafiche).') ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE "%'.$term.'%"';
}

//$query .= Modules::getAdditionalsQuery('Anagrafiche');

$rs = $dbo->fetchArray($query);

$plugin = $dbo->fetchArray("SELECT id FROM zz_plugins WHERE name='Referenti'");

foreach ($rs as $r) {
    $result = [];

    $result['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$r['id'].'#tab_'.$plugin[0]['id'];
    $result['title'] = $r['nome'];
    $result['category'] = 'Referenti';

    // Campi da evidenziare
    $result['labels'] = [];
    foreach ($fields as $name => $value) {
        if (str_contains($r[$name], $term)) {
            $text = str_replace($term, "<span class='highlight'>".$term.'</span>', $r[$name]);

            $result['labels'][] = $name.': '.$text.'<br/>';
        }
    }

    // Aggiunta nome anagrafica come ultimo campo
    if (sizeof($ragioni_sociali) > 1) {
        $result['labels'][] = 'Anagrafica: '.$ragioni_sociali[$r['idanagrafica']].'<br/>';
    }

    $results[] = $result;
}
