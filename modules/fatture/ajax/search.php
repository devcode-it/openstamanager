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
    'Note aggiuntive' => 'note_aggiuntive',
    'Buono d\'ordine' => 'buono_ordine',
    'Righe' => 'righe.descrizione',
];

$query = 'SELECT *, `co_documenti`.`id`, `co_tipidocumento_lang`.`title` AS tipologia';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') LEFT JOIN (SELECT GROUP_CONCAT(`descrizione` SEPARATOR " -- ") AS "descrizione", `iddocumento`, SUM(`qta`) AS "totale_quantita", SUM(`costo_unitario` * `qta`) AS "totale_acquisto", SUM(`prezzo_unitario` * `qta` - `sconto`) AS "totale_vendita" FROM co_righe_documenti GROUP BY `iddocumento`) righe ON `righe`.`iddocumento`=`co_documenti`.`id` WHERE `idanagrafica` IN('.implode(',', $idanagrafiche).') ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE '.prepare('%'.$term.'%');
}

// Aggiunta ricerca diretta negli articoli
$query .= ' OR `co_documenti`.`id` IN (SELECT DISTINCT `co_righe_documenti`.`iddocumento` FROM `co_righe_documenti` LEFT JOIN `mg_articoli` ON `co_righe_documenti`.`idarticolo` = `mg_articoli`.`id` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `mg_articoli`.`codice` LIKE '.prepare('%'.$term.'%').' OR `mg_articoli_lang`.`title` LIKE '.prepare('%'.$term.'%').')';

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $module = ($r['dir'] == 'uscita') ? 'Fatture di acquisto' : 'Fatture di vendita';
    $link_id = Module::where('name', $module)->first()->id;

    $numero = empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'];

    $result['link'] = base_path_osm().'/editor.php?id_module='.$link_id.'&id_record='.$r['id'];
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
    $articoli_query = 'SELECT CONCAT(COALESCE(`mg_articoli`.`codice`, ""), IF(`mg_articoli`.`codice` IS NOT NULL AND `mg_articoli_lang`.`title` IS NOT NULL, " - ", ""), COALESCE(`mg_articoli_lang`.`title`, "")) AS articolo, `co_righe_documenti`.`qta`, `co_righe_documenti`.`prezzo_unitario`, `co_righe_documenti`.`costo_unitario`, `co_righe_documenti`.`sconto`, `co_righe_documenti`.`subtotale` FROM co_righe_documenti LEFT JOIN `mg_articoli` ON `co_righe_documenti`.`idarticolo` = `mg_articoli`.`id` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_righe_documenti`.`iddocumento` = '.prepare($r['id']).' AND `mg_articoli`.`id` IS NOT NULL AND (CONCAT(COALESCE(`mg_articoli`.`codice`, ""), " - ", COALESCE(`mg_articoli_lang`.`title`, "")) LIKE '.prepare('%'.$term.'%').' OR `mg_articoli`.`codice` LIKE '.prepare('%'.$term.'%').' OR `mg_articoli_lang`.`title` LIKE '.prepare('%'.$term.'%').')';
    $articoli_rs = $dbo->fetchArray($articoli_query);

    $articoli = [];
    $quantita_totale = 0;
    $valore_totale = 0;

    foreach ($articoli_rs as $articolo) {
        if (!empty(trim((string) $articolo['articolo']))) {
            $articoli[] = $articolo['articolo'];
            $quantita_totale += $articolo['qta'];

            // Calcolo del valore in base al tipo di documento
            if ($r['dir'] == 'uscita') {
                // Fattura di acquisto - usa costo unitario
                $valore_totale += $articolo['costo_unitario'] * $articolo['qta'];
            } else {
                // Fattura di vendita - usa prezzo unitario meno sconto
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
            if ($r['dir'] == 'uscita') {
                $result['labels'][] = 'Valore acquisto: '.moneyFormat($valore_totale).'<br/>';
            } else {
                $result['labels'][] = 'Valore vendita: '.moneyFormat($valore_totale).'<br/>';
            }
        }
    }

    $results[] = $result;
}
