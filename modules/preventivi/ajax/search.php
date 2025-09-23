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

$link_id = Module::where('name', 'Preventivi')->first()->id;

$results = [];

$fields = [
    'Codice preventivo' => 'numero',
    'Nome' => 'nome',
    'Descrizione' => '`co_preventivi`.`descrizione`',
    'Righe' => 'righe.descrizione',
    'Data' => 'data_accettazione',
];

$query = 'SELECT *';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM co_preventivi LEFT JOIN (SELECT GROUP_CONCAT(`descrizione` SEPARATOR " -- ") AS "descrizione", `idpreventivo`, SUM(`qta`) AS "totale_quantita", SUM(`subtotale`) AS "totale_vendita" FROM co_righe_preventivi GROUP BY `idpreventivo`) righe ON `righe`.`idpreventivo`=`co_preventivi`.`id` WHERE idanagrafica IN('.implode(',', $idanagrafiche).') ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE "%'.$term.'%"';
}

// Aggiunta ricerca diretta negli articoli
$query .= ' OR `co_preventivi`.`id` IN (SELECT DISTINCT `co_righe_preventivi`.`idpreventivo` FROM `co_righe_preventivi` LEFT JOIN `mg_articoli` ON `co_righe_preventivi`.`idarticolo` = `mg_articoli`.`id` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `mg_articoli`.`codice` LIKE "%'.$term.'%" OR `mg_articoli_lang`.`title` LIKE "%'.$term.'%")';

$query .= Modules::getAdditionalsQuery(Module::where('name', 'Preventivi')->first()->id);

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $result['link'] = base_path().'/editor.php?id_module='.$link_id.'&id_record='.$r['id'];
    $result['title'] = 'Preventivo '.$r['numero'];

    if ($r['data_accettazione'] && $r['data_accettazione'] != '0000-00-00') {
        $result['title'] .= ' del '.Translator::dateToLocale($r['data_accettazione']);
    }

    $result['category'] = 'Preventivi';

    // Campi da evidenziare
    $result['labels'] = [];
    foreach ($fields as $name => $value) {
        if (string_contains($r[$name], $term)) {
            $text = str_replace($term, "<span class='highlight'>".$term.'</span>', $r[$name]);

            $result['labels'][] = $name.': '.$text.'<br/>';
        }
    }

    // Aggiunta nome anagrafica come ultimo campo
    if (sizeof($ragioni_sociali) > 1) {
        $result['labels'][] = 'Anagrafica: '.$ragioni_sociali[$r['idanagrafica']].'<br/>';
    }

    // Recupero solo gli articoli che corrispondono al termine di ricerca con quantità e valori
    $articoli_query = 'SELECT CONCAT(COALESCE(`mg_articoli`.`codice`, ""), IF(`mg_articoli`.`codice` IS NOT NULL AND `mg_articoli_lang`.`title` IS NOT NULL, " - ", ""), COALESCE(`mg_articoli_lang`.`title`, "")) AS articolo, `co_righe_preventivi`.`qta`, `co_righe_preventivi`.`prezzo_unitario`, `co_righe_preventivi`.`sconto`, `co_righe_preventivi`.`subtotale` FROM co_righe_preventivi LEFT JOIN `mg_articoli` ON `co_righe_preventivi`.`idarticolo` = `mg_articoli`.`id` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_righe_preventivi`.`idpreventivo` = '.prepare($r['id']).' AND `mg_articoli`.`id` IS NOT NULL AND (CONCAT(COALESCE(`mg_articoli`.`codice`, ""), " - ", COALESCE(`mg_articoli_lang`.`title`, "")) LIKE "%'.$term.'%" OR `mg_articoli`.`codice` LIKE "%'.$term.'%" OR `mg_articoli_lang`.`title` LIKE "%'.$term.'%")';
    $articoli_rs = $dbo->fetchArray($articoli_query);

    $articoli = [];
    $quantita_totale = 0;
    $valore_totale = 0;

    foreach ($articoli_rs as $articolo) {
        if (!empty(trim($articolo['articolo']))) {
            $articoli[] = $articolo['articolo'];
            $quantita_totale += $articolo['qta'];

            // Calcolo del valore per preventivi (sempre vendita)
            $valore_totale += $articolo['prezzo_unitario'] * $articolo['qta'] - $articolo['sconto'];
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
            $result['labels'][] = 'Valore: '.moneyFormat($valore_totale).'<br/>';
        }
    }

    $results[] = $result;
}
