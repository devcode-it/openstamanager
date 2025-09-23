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

$link_id = Module::where('name', 'Interventi')->first()->id;

$results = [];

$fields = [
    'Codice intervento' => '`in_interventi`.`codice`',
    'Data intervento' => 'COALESCE((SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id), in_interventi.data_richiesta)',
    'Data richiesta intervento' => '`in_interventi`.`data_richiesta`',
    'Sede intervento' => 'info_sede',
    'Richiesta' => 'richiesta',
    'Descrizione' => '`in_interventi`.`descrizione`',
    'Informazioni aggiuntive' => 'informazioniaggiuntive',
];

$query = 'SELECT in_interventi.*,
    COALESCE(
        (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id),
        in_interventi.data_richiesta
    ) AS data,
    GROUP_CONCAT(DISTINCT CONCAT(
        `mg_articoli`.`codice`, " - ", COALESCE(mg_articoli_lang.title, "")
    ) ORDER BY `mg_articoli`.`codice` SEPARATOR "<br>") AS articoli_inclusi,
    righe.totale_quantita,
    righe.totale_acquisto,
    righe.totale_vendita';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM in_interventi
    LEFT JOIN in_righe_interventi ON in_interventi.id = in_righe_interventi.idintervento AND in_righe_interventi.idarticolo IS NOT NULL AND in_righe_interventi.idarticolo != 0
    LEFT JOIN mg_articoli ON in_righe_interventi.idarticolo = mg_articoli.id
    LEFT JOIN mg_articoli_lang ON (mg_articoli.id = mg_articoli_lang.id_record AND mg_articoli_lang.id_lang = '.prepare(Models\Locale::getDefault()->id).')
    LEFT JOIN (SELECT `idintervento`, SUM(`qta`) AS "totale_quantita", SUM(`costo_unitario` * `qta`) AS "totale_acquisto", SUM(`prezzo_unitario` * `qta` - `sconto`) AS "totale_vendita" FROM in_righe_interventi GROUP BY `idintervento`) righe ON `righe`.`idintervento`=`in_interventi`.`id` ';

$where = [];
foreach ($fields as $name => $value) {
    $where[] = $value.' LIKE "%'.$term.'%"';
}

// Aggiunta ricerca negli articoli (con controllo NULL)
$where[] = '(`mg_articoli`.`codice` IS NOT NULL AND `mg_articoli`.`codice` LIKE "%'.$term.'%")';
$where[] = '(`mg_articoli_lang`.`title` IS NOT NULL AND `mg_articoli_lang`.`title` LIKE "%'.$term.'%")';

$query .= ' WHERE ('.implode(' OR ', $where).') ';

$query .= ' GROUP BY in_interventi.id ';

$query .= ' '.Modules::getAdditionalsQuery(Module::where('name', 'Interventi')->first()->id, null, false);

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $result['link'] = base_path().'/editor.php?id_module='.$link_id.'&id_record='.$r['id'];

    // Gestione della data: se è NULL o vuota, non mostrarla
    $data_intervento = '';
    if (!empty($r['data_richiesta']) && $r['data_richiesta'] != '0000-00-00 00:00:00') {
        $data_intervento = ' del '.Translator::dateToLocale($r['data_richiesta']);
    }

    $result['title'] = 'Intervento '.$r['codice'].$data_intervento;
    $result['category'] = 'Interventi';

    // Campi da evidenziare
    $result['labels'] = [];
    foreach ($fields as $name => $value) {
        if (string_contains($r[$name], $term)) {
            $text = str_replace($term, "<span class='highlight'>".$term.'</span>', $r[$name]);

            $result['labels'][] = $name.': '.$text.'<br/>';
        }
    }

    // Visualizzazione degli articoli inclusi nell'intervento solo se il termine di ricerca corrisponde agli articoli di questo specifico intervento
    if (!empty($r['articoli_inclusi'])) {
        // Verifica se il termine di ricerca è presente negli articoli di questo intervento
        $articoli_match = false;
        if (stripos($r['articoli_inclusi'], $term) !== false) {
            $articoli_match = true;
        }

        if ($articoli_match) {
            $result['labels'][] = $r['articoli_inclusi'].'<br/>';
        }
    }

    // Aggiunta nome anagrafica come ultimo campo
    if (sizeof($ragioni_sociali) > 1) {
        $result['labels'][] = 'Anagrafica: '.$ragioni_sociali[$r['idanagrafica']].'<br/>';
    }

    // Mostra quantità e valori solo se il termine di ricerca corrisponde agli articoli di questo intervento
    if (!empty($r['articoli_inclusi']) && stripos($r['articoli_inclusi'], $term) !== false) {
        // Aggiunta quantità totale
        if (!empty($r['totale_quantita']) && $r['totale_quantita'] > 0) {
            $result['labels'][] = 'Quantità totale: '.numberFormat($r['totale_quantita'], setting('Cifre decimali per quantità')).'<br/>';
        }

        // Aggiunta totali di acquisto e vendita per interventi
        if (!empty($r['totale_acquisto']) && $r['totale_acquisto'] > 0) {
            $result['labels'][] = 'Valore acquisto: '.moneyFormat($r['totale_acquisto']).'<br/>';
        }
        if (!empty($r['totale_vendita']) && $r['totale_vendita'] > 0) {
            $result['labels'][] = 'Valore vendita: '.moneyFormat($r['totale_vendita']).'<br/>';
        }
    }

    $results[] = $result;
}
