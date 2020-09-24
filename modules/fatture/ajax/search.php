<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../../core.php';

$fields = [
    'Numero' => 'numero',
    'Numero secondario' => 'numero_esterno',
    'Data' => 'data',
    'Note' => 'note',
    'Note aggiuntive' => 'note_aggiuntive',
    'Buono d\'ordine' => 'buono_ordine',
    'Righe' => 'righe.descrizione',
];

$query = 'SELECT *, co_documenti.id, co_tipidocumento.descrizione AS tipologia';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id LEFT JOIN (SELECT GROUP_CONCAT(descrizione SEPARATOR " -- ") AS "descrizione", iddocumento FROM co_righe_documenti GROUP BY iddocumento) righe ON righe.iddocumento=co_documenti.id WHERE idanagrafica IN('.implode(',', $idanagrafiche).') ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE "%'.$term.'%"';
}

//$query .= Modules::getAdditionalsQuery('Interventi');

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $module = ($r['dir'] == 'uscita') ? 'Fatture di acquisto' : 'Fatture di vendita';
    $link_id = Modules::get($module)['id'];

    $numero = empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'];

    $result['link'] = base_path().'/editor.php?id_module='.$link_id.'&id_record='.$r['id'];
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
