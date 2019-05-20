<?php

include_once __DIR__.'/../../../core.php';

$link_id = Modules::get('Interventi')['id'];

$fields = [
    'Codice intervento' => 'codice',
    'Data intervento' => '(SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id)',
    'Data richiesta intervento' => 'data_richiesta',
    'Sede intervento' => 'info_sede',
    'Richiesta' => 'richiesta',
    'Descrizione' => 'descrizione',
    'Informazioni aggiuntive' => 'informazioniaggiuntive',
];

$query = 'SELECT *, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM in_interventi ';

$where = [];
foreach ($fields as $name => $value) {
    $where[] = $value.' LIKE "%'.$term.'%"';
}

$query .= ' WHERE ('.implode(' OR ', $where).') ';

$query .= ' '.Modules::getAdditionalsQuery('Interventi');

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $result['link'] = ROOTDIR.'/editor.php?id_module='.$link_id.'&id_record='.$r['id'];
    $result['title'] = 'Intervento '.$r['codice'].' del '.Translator::dateToLocale($r['data']);
    $result['category'] = 'Interventi';

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
