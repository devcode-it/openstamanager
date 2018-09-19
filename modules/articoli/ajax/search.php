<?php

include_once __DIR__.'/../../../core.php';

$link_id = Modules::get('Articoli')['id'];

$fields = [
    'Codice' => 'codice',
    'Descrizione' => 'descrizione',
    'Categoria' => '(SELECT nome FROM mg_categorie WHERE mg_categorie.id =  mg_articoli.id_categoria)',
    'Subcategoria' => '(SELECT nome FROM mg_categorie WHERE mg_categorie.id =  mg_articoli.id_sottocategoria)',
    'Note' => 'note',
];

$query = 'SELECT *';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM mg_articoli WHERE 1=0 ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE "%'.$term.'%"';
}

$query .= Modules::getAdditionalsQuery('Articoli');

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $result['link'] = ROOTDIR.'/editor.php?id_module='.$link_id.'&id_record='.$r['id'];
    $result['title'] = $r['codice'].' - '.$r['descrizione'];
    $result['category'] = 'Articoli';

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
