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

$link_id = (new Module())->getByField('name', 'Articoli', Models\Locale::getPredefined()->id);
$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

$show_prezzi = Auth::user()['gruppo'] != 'Tecnici' || (Auth::user()['gruppo'] == 'Tecnici' && setting('Mostra i prezzi al tecnico'));

$fields = [
    'codice' => 'mg_articoli.codice',
    'barcode' => 'mg_articoli.barcode',
    'descrizione' => 'mg_articoli_lang.name',
    'categoria' => '(SELECT `name` FROM `mg_categorie` LEFT JOIN `mg_categorie_lang` ON (`mg_categorie`.`id` = `mg_categorie_lang`.`id_record` AND `mg_categorie_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `mg_categorie`.`id` =  `mg_articoli`.`id_categoria`)',
    'subcategoria' => '(SELECT `name` FROM `mg_categorie` LEFT JOIN `mg_categorie_lang` ON (`mg_categorie`.`id` = `mg_categorie_lang`.`id_record` AND `mg_categorie_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `mg_categorie`.`id` =  `mg_articoli`.`id_sottocategoria`)',
    'Note' => 'note',
];

$query = 'SELECT *';

foreach ($fields as $name => $value) {
    $query .= ', '.$value." AS '".str_replace("'", "\'", $name)."'";
}

$query .= ' FROM `mg_articoli` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE deleted_at IS NULL AND (1=0 ';

foreach ($fields as $name => $value) {
    $query .= ' OR '.$value.' LIKE "%'.$term.'%"';
}
$query .= ')';

$query .= Modules::getAdditionalsQuery('Articoli');

$rs = $dbo->fetchArray($query);

foreach ($rs as $r) {
    $result = [];

    $result['link'] = base_path().'/editor.php?id_module='.$link_id.'&id_record='.$r['id'];
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
