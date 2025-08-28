<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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
use Models\Module;

$results = [];

$fields = [
    'Numero' => 'numero',
    'Numero secondario' => 'numero_esterno',
    'Data' => 'data',
    'Note' => 'note',
    'Righe' => '(SELECT GROUP_CONCAT(descrizione SEPARATOR \' -- \') FROM dt_righe_ddt WHERE dt_righe_ddt.idddt = dt_ddt.id)',
];

$query = 'SELECT *, `dt_ddt`.`id`, `dt_tipiddt_lang`.`title` AS tipologia';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt`=`dt_tipiddt`.`id` LEFT JOIN `dt_tipiddt_lang` ON (`dt_tipiddt`.`id`= `dt_tipiddt_lang`.`id_record` AND `dt_tipiddt_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `idanagrafica` IN('.implode(',', $idanagrafiche).') ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE "%'.$term.'%"';
}

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $module = ($r['dir'] == 'uscita') ? 'Ddt in entrata' : 'Ddt in uscita';
    $link_id = Module::where('name', $module)->first()->id;

    $numero = empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'];

    $result['link'] = base_path().'/editor.php?id_module='.$link_id.'&id_record='.$r['id'];
    $result['title'] = $r['tipologia'].' num. '.$numero.' del '.Translator::dateToLocale($r['data']);
    $result['category'] = $r['tipologia'];

    // Campi da evidenziare
    $result['labels'] = [];
    foreach ($fields as $name => $value) {
        if (string_contains($r[$name], $term)) {
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
