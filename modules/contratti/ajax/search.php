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

$link_id = Module::where('name', 'Contratti')->first()->id;

$results = [];

$fields = [
    'Numero' => 'co_contratti.numero',
    'Nome' => 'co_contratti.nome',
    'Descrizione' => 'co_contratti.descrizione',
    'Data accettazione' => 'co_contratti.data_accettazione',
    'Righe' => 'righe.descrizione',
];

$query = 'SELECT co_contratti.*';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM co_contratti LEFT JOIN (SELECT GROUP_CONCAT(`descrizione` SEPARATOR " -- ") AS "descrizione", `idcontratto`, SUM(`qta`) AS "totale_quantita", SUM(`subtotale`) AS "totale_vendita" FROM co_righe_contratti GROUP BY `idcontratto`) righe ON `righe`.`idcontratto`=`co_contratti`.`id` WHERE 1=0 ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE '.prepare('%'.$term.'%');
}

// Ricerca anche per anagrafica se il termine corrisponde a una ragione sociale
$query .= ' OR co_contratti.idanagrafica IN (SELECT idanagrafica FROM an_anagrafiche WHERE ragione_sociale LIKE '.prepare('%'.$term.'%').')';

// Ricerca anche negli articoli associati al contratto
$query .= ' OR co_contratti.id IN (
    SELECT DISTINCT co_righe_contratti.idcontratto
    FROM co_righe_contratti
    LEFT JOIN mg_articoli ON co_righe_contratti.idarticolo = mg_articoli.id
    LEFT JOIN mg_articoli_lang ON (mg_articoli.id = mg_articoli_lang.id_record AND mg_articoli_lang.id_lang = '.prepare(Models\Locale::getDefault()->id).')
    WHERE mg_articoli.codice LIKE '.prepare('%'.$term.'%').'
    OR mg_articoli_lang.title LIKE '.prepare('%'.$term.'%').'
)';

$query .= Modules::getAdditionalsQuery(Module::where('name', 'Contratti')->first()->id);

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $result['link'] = base_path().'/editor.php?id_module='.$link_id.'&id_record='.$r['id'];
    $result['title'] = 'Contratto '.$r['numero'];

    if ($r['data_accettazione'] && $r['data_accettazione'] != '0000-00-00') {
        $result['title'] .= ' del '.Translator::dateToLocale($r['data_accettazione']);
    }

    $result['category'] = 'Contratti';

    // Campi da evidenziare
    $result['labels'] = [];
    foreach ($fields as $name => $value) {
        if (string_contains($r[$name], $term)) {
            $text = $r[$name];

            // Formattazione speciale per la data di accettazione
            if ($name == 'Data accettazione' && $text != '0000-00-00' && !empty($text)) {
                $text = Translator::dateToLocale($text);
            }

            $text = str_replace($term, "<span class='highlight'>".$term.'</span>', $text);
            $result['labels'][] = $name.': '.$text.'<br/>';
        }
    }

    // Aggiunta nome anagrafica
    $anagrafica_query = 'SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica = '.prepare($r['idanagrafica']);
    $anagrafica_rs = $dbo->fetchOne($anagrafica_query);
    if (!empty($anagrafica_rs['ragione_sociale'])) {
        $result['labels'][] = 'Anagrafica: '.$anagrafica_rs['ragione_sociale'].'<br/>';
    }

    // Recupero solo gli articoli che corrispondono al termine di ricerca con quantità e valori
    $articoli_query = 'SELECT CONCAT(COALESCE(`mg_articoli`.`codice`, ""), IF(`mg_articoli`.`codice` IS NOT NULL AND `mg_articoli_lang`.`title` IS NOT NULL, " - ", ""), COALESCE(`mg_articoli_lang`.`title`, "")) AS articolo, `co_righe_contratti`.`qta`, `co_righe_contratti`.`prezzo_unitario`, `co_righe_contratti`.`sconto`, `co_righe_contratti`.`subtotale` FROM co_righe_contratti LEFT JOIN `mg_articoli` ON `co_righe_contratti`.`idarticolo` = `mg_articoli`.`id` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_righe_contratti`.`idcontratto` = '.prepare($r['id']).' AND `mg_articoli`.`id` IS NOT NULL AND (CONCAT(COALESCE(`mg_articoli`.`codice`, ""), " - ", COALESCE(`mg_articoli_lang`.`title`, "")) LIKE "%'.$term.'%" OR `mg_articoli`.`codice` LIKE "%'.$term.'%" OR `mg_articoli_lang`.`title` LIKE "%'.$term.'%")';
    $articoli_rs = $dbo->fetchArray($articoli_query);

    $articoli = [];
    $quantita_totale = 0;
    $valore_totale = 0;

    foreach ($articoli_rs as $articolo) {
        if (!empty(trim($articolo['articolo']))) {
            $articoli[] = $articolo['articolo'];
            $quantita_totale += $articolo['qta'];

            // Calcolo del valore per contratti (sempre vendita)
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
