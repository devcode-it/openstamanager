<?php

include_once __DIR__.'/../../../core.php';

$fields = [
    'Numero' => 'numero',
    'Numero secondario' => 'numero_esterno',
    'Data' => 'data',
    'Note' => 'note',
    'Note aggiuntive' => 'note_aggiuntive',
    'Buono d\'ordine' => 'buono_ordine',
    'Righe' => '(SELECT GROUP_CONCAT(descrizione SEPARATOR \' -- \') FROM co_righe_documenti WHERE co_righe_documenti.iddocumento = co_documenti.id)',
];

$query = 'SELECT *, co_documenti.id, co_tipidocumento.descrizione AS tipologia';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE idanagrafica IN('.implode(',', $idanagrafiche).') ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE "%'.$term.'%"';
}

//$query .= Modules::getAdditionalsQuery('Interventi');

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $module = ($r['dir'] == 'uscita') ? 'Fatture di acquisto' : 'Fatture di vendita';
    $id_module = Modules::get($module)['id'];

    $numero = empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'];

    $result['link'] = ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$r['id'];
    $result['title'] = $r['tipologia'].' num. '.$numero.' del '.Translator::dateToLocale($r['data']);
    $result['category'] = $r['tipologia'];

    // Campi da evidenziare
    $result['labels'] = [];
    foreach ($fields as $name => $value) {
        if (str_contains($r[$name], $term)) {
            if ($name == 'Righe') {
                $result['labels'][] = tr('Termine presente nelle righe del documento').'<br/>';
            } else {
                $text = str_replace($term, "<span class='highlight'>".$term.'</span>', $r[$name]);

                $result['labels'][] = $name.': '.$text.'<br/>';
            }
        }
    }

    // Aggiunta nome anagrafica come ultimo campo
    if (sizeof($ragioni_sociali) > 1) {
        $result['labels'][] = 'Anagrafica: '.$ragioni_sociali[$r['idanagrafica']].'<br/>';
    }

    $results[] = $result;
}
