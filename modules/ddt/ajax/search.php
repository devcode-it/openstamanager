<?php

include_once __DIR__.'/../../../core.php';

$fields = [
    'Numero' => 'numero',
    'Numero secondario' => 'numero_esterno',
    'Data' => 'data',
    'Note' => 'note',
    'Righe' => '(SELECT GROUP_CONCAT(descrizione SEPARATOR \' -- \') FROM dt_righe_ddt WHERE dt_righe_ddt.idddt = dt_ddt.id)',
];

$query = 'SELECT *, dt_ddt.id, dt_tipiddt.descrizione AS tipologia';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM dt_ddt INNER JOIN dt_tipiddt ON dt_ddt.id_tipo_ddt=dt_tipiddt.id WHERE idanagrafica IN('.implode(',', $idanagrafiche).') ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE "%'.$term.'%"';
}

//$query .= Modules::getAdditionalsQuery('Interventi');

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $module = ($r['dir'] == 'uscita') ? 'Ddt di acquisto' : 'Ddt di vendita';
    $link_id = Modules::get($module)['id'];

    $numero = empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'];

    $result['link'] = ROOTDIR.'/editor.php?id_module='.$link_id.'&id_record='.$r['id'];
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
