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

if(!empty(setting('Magazzino cespiti'))){

    $id_module = Modules::get('Articoli')['id'];
    $structure = Modules::get($id_module);

    // RISULTATI VISIBILI
    Util\Query::setSegments(false);
    $query = Query::getQuery($structure, $where, 0, []);

    $query = Modules::replaceAdditionals($id_module, $query);

    $query = preg_replace('/FROM `mg_articoli`/', ' FROM mg_articoli LEFT JOIN (SELECT idarticolo, SUM(qta) AS qta_totale FROM mg_movimenti WHERE idsede='.setting('Magazzino cespiti').' GROUP BY idarticolo) movimenti ON movimenti.idarticolo=mg_articoli.id ', $query);

    // Modifiche alla query principale
    $query = preg_replace('/^SELECT /', 'SELECT mg_articoli.prezzo_vendita,', $query);
    $query = preg_replace('/^SELECT /', 'SELECT mg_articoli.um,', $query);
    $query = preg_replace('/^SELECT /', 'SELECT movimenti.qta_totale,', $query);

    $query = str_replace('1=1', '1=1 AND cespiti=1', $query);
    $query = str_replace('cespiti=0', 'cespiti=1', $query);

    if (post('tipo') == 'nozero') {
        $query = str_replace('2=2', '2=2 AND mg_articoli.qta > 0', $query);
    }

    $data = Query::executeAndCount($query);

    echo '
    <h3>'.tr('Inventario cespiti').'</h3>

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

    $totale_qta = 0;
    $totali = [];
    foreach ($data['results'] as $r) {
        $valore_magazzino = $r['acquisto'] * $r['qta_totale'];

        echo '
            <tr>
                <td>'.$r['Codice'].'</td>
                <td>'.$r['Categoria'].'</td>
                <td>'.$r['Descrizione'].'</td>
                <td class="text-right">'.moneyFormat($r['prezzo_vendita']).'</td>
                <td class="text-right">'.Translator::numberToLocale($r['qta_totale']).' '.$r['um'].'</td>
                <td class="text-right">'.moneyFormat($r['acquisto']).'</td>
                <td class="text-right">'.moneyFormat($valore_magazzino).'</td>
            </tr>';

        $totale_qta += $r['qta_totale'];
        $totali[] = $valore_magazzino;
    }

    // Totali
    $totale_acquisto = sum($totali);
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
}