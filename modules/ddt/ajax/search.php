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
    'Righe' => 'righe.descrizione',
];

$query = 'SELECT *, `dt_ddt`.`id`, `dt_tipiddt_lang`.`title` AS tipologia';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt`=`dt_tipiddt`.`id` LEFT JOIN `dt_tipiddt_lang` ON (`dt_tipiddt`.`id`= `dt_tipiddt_lang`.`id_record` AND `dt_tipiddt_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') LEFT JOIN (SELECT GROUP_CONCAT(`descrizione` SEPARATOR " -- ") AS "descrizione", `idddt`, SUM(`qta`) AS "totale_quantita", SUM(`costo_unitario` * `qta`) AS "totale_acquisto", SUM(`prezzo_unitario` * `qta` - `sconto`) AS "totale_vendita" FROM dt_righe_ddt GROUP BY `idddt`) righe ON `righe`.`idddt`=`dt_ddt`.`id` WHERE `idanagrafica` IN('.implode(',', $idanagrafiche).') ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE "%'.$term.'%"';
}

// Aggiunta ricerca diretta negli articoli
$query .= ' OR `dt_ddt`.`id` IN (SELECT DISTINCT `dt_righe_ddt`.`idddt` FROM `dt_righe_ddt` LEFT JOIN `mg_articoli` ON `dt_righe_ddt`.`idarticolo` = `mg_articoli`.`id` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `mg_articoli`.`codice` LIKE "%'.$term.'%" OR `mg_articoli_lang`.`title` LIKE "%'.$term.'%")';

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

    // Recupero solo gli articoli che corrispondono al termine di ricerca con quantità e valori
    $articoli_query = 'SELECT CONCAT(COALESCE(`mg_articoli`.`codice`, ""), IF(`mg_articoli`.`codice` IS NOT NULL AND `mg_articoli_lang`.`title` IS NOT NULL, " - ", ""), COALESCE(`mg_articoli_lang`.`title`, "")) AS articolo, `dt_righe_ddt`.`qta`, `dt_righe_ddt`.`prezzo_unitario`, `dt_righe_ddt`.`costo_unitario`, `dt_righe_ddt`.`sconto`, `dt_righe_ddt`.`subtotale` FROM dt_righe_ddt LEFT JOIN `mg_articoli` ON `dt_righe_ddt`.`idarticolo` = `mg_articoli`.`id` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `dt_righe_ddt`.`idddt` = '.prepare($r['id']).' AND `mg_articoli`.`id` IS NOT NULL AND (CONCAT(COALESCE(`mg_articoli`.`codice`, ""), " - ", COALESCE(`mg_articoli_lang`.`title`, "")) LIKE "%'.$term.'%" OR `mg_articoli`.`codice` LIKE "%'.$term.'%" OR `mg_articoli_lang`.`title` LIKE "%'.$term.'%")';
    $articoli_rs = $dbo->fetchArray($articoli_query);

    $articoli = [];
    $quantita_totale = 0;
    $valore_totale = 0;

    foreach ($articoli_rs as $articolo) {
        if (!empty(trim($articolo['articolo']))) {
            $articoli[] = $articolo['articolo'];
            $quantita_totale += $articolo['qta'];

            // Calcolo del valore in base al tipo di DDT
            if ($r['dir'] == 'entrata') {
                // DDT in entrata - usa costo unitario
                $valore_totale += $articolo['costo_unitario'] * $articolo['qta'];
            } else {
                // DDT in uscita - usa prezzo unitario meno sconto
                $valore_totale += $articolo['prezzo_unitario'] * $articolo['qta'] - $articolo['sconto'];
            }
        }
    }

    // Aggiunta solo degli articoli che corrispondono alla ricerca
    if (!empty($articoli)) {
        $result['labels'][] = implode(', ', $articoli).'<br/>';

        // Aggiunta quantità dell'articolo cercato
        if ($quantita_totale > 0) {
            $result['labels'][] = 'Quantità: '.numberFormat($quantita_totale, setting('Cifre decimali per quantità')).'<br/>';
        }

        // Aggiunta valore dell'articolo cercato
        if ($valore_totale > 0) {
            if ($r['dir'] == 'entrata') {
                $result['labels'][] = 'Valore acquisto: '.moneyFormat($valore_totale).'<br/>';
            } else {
                $result['labels'][] = 'Valore vendita: '.moneyFormat($valore_totale).'<br/>';
            }
        }
    }

    $results[] = $result;
}
