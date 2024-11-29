<?php

include_once __DIR__.'/../../../core.php';
use Models\Module;

$link_id = Module::where('name', 'Automezzi')->first()->id;

$fields = [
    'Nome' => 'nome',
    'Descrizione' => 'descrizione',
    'Targa' => 'targa',
];

$query = 'SELECT * FROM an_sedi WHERE 1=0 ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE '.prepare('%'.$term.'%');
}

$query .= Modules::getAdditionalsQuery(Module::where('name', 'Automezzi')->first()->id);

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $result['link'] = ROOTDIR.'/editor.php?id_module='.$link_id.'&id_record='.$r['id'];
    $result['title'] = $r['nome'];
    $result['category'] = tr('Automezzi');

    // Campi da evidenziare
    $result['labels'] = [];
    foreach ($fields as $name => $value) {
        if (str_contains((string) $r[$name], (string) $term)) {
            $text = str_replace($term, "<span class='highlight'>".$term.'</span>', $r[$name]);

            $result['labels'][] = $name.': '.$text.'<br/>';
        }
    }

    $results[] = $result;
}
