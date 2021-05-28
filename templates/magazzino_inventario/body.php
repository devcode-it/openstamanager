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

use Util\Query;

include_once __DIR__.'/../../core.php';

$id_module = Modules::get('Articoli')['id'];

// Valori di ricerca
$where['servizio'] = '0';

foreach ($_SESSION['module_'.$id_module] as $name => $value) {
    if (preg_match('/^search_(.+?)$/', $name, $m)) {
        $where[$m[1]] = $value;
    }
}

$period_end = $_SESSION['period_end'];

$structure = Modules::get($id_module);

// RISULTATI VISIBILI
Util\Query::setSegments(false);
$query = Query::getQuery($structure, $where, 0, []);

$query = Modules::replaceAdditionals($id_module, $query);

// Modifiche alla query principale
$query = preg_replace('/FROM `mg_articoli`/', ' FROM mg_articoli LEFT JOIN (SELECT idarticolo, SUM(qta) AS qta_totale FROM mg_movimenti WHERE data <='.prepare($period_end).' GROUP BY idarticolo) movimenti ON movimenti.idarticolo=mg_articoli.id ', $query);

$query = preg_replace('/^SELECT /', 'SELECT mg_articoli.prezzo_acquisto,', $query);
$query = preg_replace('/^SELECT /', 'SELECT mg_articoli.prezzo_vendita,', $query);
$query = preg_replace('/^SELECT /', 'SELECT mg_articoli.um,', $query);
$query = preg_replace('/^SELECT /', 'SELECT movimenti.qta_totale,', $query);

if (post('tipo') == 'nozero') {
    $query = str_replace('2=2', '2=2 AND movimenti.qta_totale > 0', $query);
}

$data = Query::executeAndCount($query);

echo '
<h3>'.tr('Inventario al _DATE_', [
    '_DATE_' => Translator::dateToLocale($period_end),
], ['upper' => true]).'</h3>

<table class="table table-bordered">
    <thead>
        <tr>
            <th class="text-center" width="150">'.tr('Codice', [], ['upper' => true]).'</th>
            <th class="text-center">'.tr('Categoria', [], ['upper' => true]).'</th>
            <th class="text-center">'.tr('Descrizione', [], ['upper' => true]).'</th>
            <th class="text-center" width="70">'.tr('Prezzo di vendita', [], ['upper' => true]).'</th>
            <th class="text-center" width="70">'.tr('Q.tà', [], ['upper' => true]).'</th>
            <th class="text-center" width="70">'.tr('Prezzo di acquisto', [], ['upper' => true]).'</th>
            <th class="text-center" width="90">'.tr('Valore totale', [], ['upper' => true]).'</th>
        </tr>
    </thead>

    <tbody>';

$totali = [];
foreach ($data['results'] as $r) {
    $valore_magazzino = $r['prezzo_acquisto'] * $r['qta_totale'];

    echo '
        <tr>
            <td>'.$r['Codice'].'</td>
            <td>'.$r['Categoria'].'</td>
            <td>'.$r['Descrizione'].'</td>
            <td class="text-right">'.moneyFormat($r['prezzo_vendita']).'</td>
            <td class="text-right">'.Translator::numberToLocale($r['qta_totale']).' '.$r['um'].'</td>
            <td class="text-right">'.moneyFormat($r['prezzo_acquisto']).'</td>
            <td class="text-right">'.moneyFormat($valore_magazzino).'</td>
        </tr>';

    $totali[] = $valore_magazzino;
}

// Totali
$totale_acquisto = sum($totali);
$totale_qta = sum(array_column($rs, 'qta_totale'));
echo '
    </tbody>

    <tr>
        <td colspan="3" class="text-right border-top"><b>'.tr('Totale', [], ['upper' => true]).':</b></td>
        <td class="border-top"></td>
        <td class="text-right border-top"><b>'.Translator::numberToLocale($totale_qta).'</b></td>
        <td class="border-top"></td>
        <td class="text-right border-top"><b>'.moneyFormat($totale_acquisto).'</b></td>
    </tr>
</table>';
