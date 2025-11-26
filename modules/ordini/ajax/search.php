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

$query = 'SELECT *, `or_ordini`.`id`, `or_tipiordine_lang`.`title` AS tipologia';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM `or_ordini` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine`=`or_tipiordine`.`id` LEFT JOIN `or_tipiordine_lang` ON (`or_tipiordine`.`id`= `or_tipiordine_lang`.`id_record` AND `or_tipiordine_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') LEFT JOIN (SELECT GROUP_CONCAT(`descrizione` SEPARATOR " -- ") AS "descrizione", `idordine`, SUM(`qta`) AS "totale_quantita", SUM(`costo_unitario` * `qta`) AS "totale_acquisto", SUM(`prezzo_unitario` * `qta` - `sconto`) AS "totale_vendita" FROM or_righe_ordini GROUP BY `idordine`) righe ON `righe`.`idordine`=`or_ordini`.`id` WHERE `idanagrafica` IN('.implode(',', $idanagrafiche).') ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE "%'.$term.'%"';
}

// Aggiunta ricerca diretta negli articoli
$query .= ' OR `or_ordini`.`id` IN (SELECT DISTINCT `or_righe_ordini`.`idordine` FROM `or_righe_ordini` LEFT JOIN `mg_articoli` ON `or_righe_ordini`.`idarticolo` = `mg_articoli`.`id` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `mg_articoli`.`codice` LIKE "%'.$term.'%" OR `mg_articoli_lang`.`title` LIKE "%'.$term.'%")';

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $module = ($r['dir'] == 'uscita') ? 'Ordini fornitore' : 'Ordini cliente';
    $link_id = Module::where('name', $module)->first()->id;

    $numero = empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'];

    $result['link'] = base_path_osm().'/editor.php?id_module='.$link_id.'&id_record='.$r['id'];
    $result['title'] = $r['tipologia'].' num. '.$numero.' del '.Translator::dateToLocale($r['data']);
    $result['category'] = $r['tipologia'];

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
    $articoli_query = 'SELECT CONCAT(COALESCE(`mg_articoli`.`codice`, ""), IF(`mg_articoli`.`codice` IS NOT NULL AND `mg_articoli_lang`.`title` IS NOT NULL, " - ", ""), COALESCE(`mg_articoli_lang`.`title`, "")) AS articolo, `or_righe_ordini`.`qta`, `or_righe_ordini`.`prezzo_unitario`, `or_righe_ordini`.`costo_unitario`, `or_righe_ordini`.`sconto`, `or_righe_ordini`.`subtotale` FROM or_righe_ordini LEFT JOIN `mg_articoli` ON `or_righe_ordini`.`idarticolo` = `mg_articoli`.`id` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `or_righe_ordini`.`idordine` = '.prepare($r['id']).' AND `mg_articoli`.`id` IS NOT NULL AND (CONCAT(COALESCE(`mg_articoli`.`codice`, ""), " - ", COALESCE(`mg_articoli_lang`.`title`, "")) LIKE "%'.$term.'%" OR `mg_articoli`.`codice` LIKE "%'.$term.'%" OR `mg_articoli_lang`.`title` LIKE "%'.$term.'%")';
    $articoli_rs = $dbo->fetchArray($articoli_query);

    $articoli = [];
    $quantita_totale = 0;
    $valore_totale = 0;

    foreach ($articoli_rs as $articolo) {
        if (!empty(trim((string) $articolo['articolo']))) {
            $articoli[] = $articolo['articolo'];
            $quantita_totale += $articolo['qta'];

            // Calcolo del valore in base al tipo di ordine
            if ($r['dir'] == 'uscita') {
                // Ordine fornitore - usa costo unitario
                $valore_totale += ($articolo['costo_unitario'] * $articolo['qta']);
            } else {
                // Ordine cliente - usa prezzo unitario meno sconto
                $valore_totale += ($articolo['prezzo_unitario'] * $articolo['qta'] - $articolo['sconto']);
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
            if ($r['dir'] == 'uscita') {
                $result['labels'][] = 'Valore acquisto: '.moneyFormat($valore_totale).'<br/>';
            } else {
                $result['labels'][] = 'Valore vendita: '.moneyFormat($valore_totale).'<br/>';
            }
        }
    }

    $results[] = $result;
}
