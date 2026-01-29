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

$link_id = Module::where('name', 'Articoli')->first()->id;
$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

$show_prezzi = auth_osm()->getUser()['gruppo'] != 'Tecnici' || (auth_osm()->getUser()['gruppo'] == 'Tecnici' && setting('Mostra i prezzi al tecnico'));

$results = [];

$fields = [
    'idarticolo' => '`mg_articoli`.`id`',
    'codice' => '`mg_articoli`.`codice`',
    'barcode' => '`barcode`.`lista`',
    'descrizione' => '`mg_articoli_lang`.`title`',
    'categoria' => '(SELECT `title` FROM `zz_categorie` LEFT JOIN `zz_categorie_lang` ON (`zz_categorie`.`id` = `zz_categorie_lang`.`id_record` AND `zz_categorie_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `zz_categorie`.`id` =  `mg_articoli`.`id_categoria`)',
    'subcategoria' => '(SELECT `title` FROM `zz_categorie` LEFT JOIN `zz_categorie_lang` ON (`zz_categorie`.`id` = `zz_categorie_lang`.`id_record` AND `zz_categorie_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `zz_categorie`.`id` =  `mg_articoli`.`id_sottocategoria`)',
    'Note' => 'note',
    'serial' => '`mg_prodotti`.`serial`',
];

$query = 'SELECT *';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM `mg_articoli` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') LEFT JOIN `mg_prodotti` ON `mg_prodotti`.`id_articolo` = `mg_articoli`.`id` LEFT JOIN (SELECT CASE WHEN COUNT(`mg_articoli_barcode`.`barcode`) <= 2 THEN GROUP_CONCAT(`mg_articoli_barcode`.`barcode` SEPARATOR \',\') ELSE CONCAT((SELECT GROUP_CONCAT(`b1`.`barcode` SEPARATOR \',\') FROM (SELECT `barcode` FROM `mg_articoli_barcode` `b2` WHERE `b2`.`idarticolo` = `mg_articoli_barcode`.`idarticolo` ORDER BY `b2`.`barcode` ASC) `b1`)) END AS `lista`, `mg_articoli_barcode`.`idarticolo` FROM `mg_articoli` LEFT JOIN `mg_articoli_barcode` ON `mg_articoli_barcode`.`idarticolo` = `mg_articoli`.`id` GROUP BY `mg_articoli`.`id`) AS `barcode` ON `barcode`.`idarticolo` = `mg_articoli`.`id` WHERE deleted_at IS NULL AND (1=0 ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE '.prepare('%'.$term.'%');
}
$query .= ') GROUP BY `mg_articoli`.`id`';

$query .= Modules::getAdditionalsQuery(Module::where('name', 'Articoli')->first()->id);

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $result['link'] = base_path_osm().'/editor.php?id_module='.$link_id.'&id_record='.$r['idarticolo'];
    $result['title'] = $r['codice'].' - '.$r['descrizione'].'<br>
        <small>'.
            ($show_prezzi ? '<strong>'.tr('Prezzo di vendita').':</strong> '.moneyFormat($prezzi_ivati ? $r['prezzo_vendita_ivato'] : $r['prezzo_vendita']).'<br>' : '').'
            <strong>'.tr('Q.tà').':</strong> '.Translator::numberToLocale($r['qta'], 'qta').' '.$r['um'].'
        </small>';
    $result['category'] = 'Articoli';

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

    $results[] = $result;
}
