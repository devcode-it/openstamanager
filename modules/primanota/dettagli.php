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

include_once __DIR__.'/../../core.php';
use Models\Module;

$id_conto = get('id_conto');
$conto = $dbo->fetchOne('SELECT co_pianodeiconti2.numero AS numero2, co_pianodeiconti3.numero AS numero3, co_pianodeiconti3.descrizione FROM co_pianodeiconti3 LEFT JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2 = co_pianodeiconti2.id WHERE co_pianodeiconti3.id='.prepare($id_conto));

// Calcolo totale conto da elenco movimenti di questo conto
$query = 'SELECT 
        `co_movimenti`.*,
        SUM(`totale`) AS totale,
        `dir` 
    FROM 
        `co_movimenti`
        LEFT JOIN `co_documenti` ON `co_movimenti`.`iddocumento` = `co_documenti`.`id`
        LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    WHERE 
        `co_movimenti`.`idconto`='.prepare($id_conto).' AND
        `co_movimenti`.`data` >= '.prepare($_SESSION['period_start']).' AND
        `co_movimenti`.`data` <= '.prepare($_SESSION['period_end']).'
    GROUP BY 
        `co_movimenti`.`idmastrino`
    ORDER BY 
        `co_movimenti`.`data` ASC, `co_movimenti`.`descrizione`';
$movimenti = $dbo->fetchArray($query);

echo ' 
<br><p class="text-center"><b>'.$conto['numero2'].'.'.$conto['numero3'].' '.$conto['descrizione'].'</b></p>';

if (!empty($movimenti)) {
    echo '
<table class="table table-bordered table-hover table-condensed table-striped">
    <tr>
        <th>'.tr('Causale').'</th>
        <th width="100">'.tr('Data').'</th>
        <th width="100">'.tr('Dare').'</th>
        <th width="100">'.tr('Avere').'</th>
        <th width="100">'.tr('Scalare').'</th>
    </tr>';

    $scalare = 0;
    $righe_movimenti = 0;

    // Elenco righe del partitario
    foreach ($movimenti as $movimento) {
        $scalare += $movimento['totale'];
        ++$righe_movimenti;

        if (sizeof($movimenti) - $righe_movimenti < 25) {
            echo '
    <tr>
        <td>';

            $id_modulo_fattura = ($movimento['dir'] == 'entrata') ? (new Module())->getByField('title', 'Fatture di vendita', Models\Locale::getPredefined()->id)->id_record : (new Module())->getByField('title', 'Fatture di acquisto', Models\Locale::getPredefined()->id);

            if (!empty($movimento['primanota'])) {
                echo Modules::link('Prima nota', $movimento['idmastrino'], $movimento['descrizione']);
            } else {
                echo Modules::link(($movimento['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto', $movimento['iddocumento'], $movimento['descrizione']);
            }

            echo '
        </td>';

            // Data
            echo '
        <td>
            '.dateFormat($movimento['data']).'
        </td>';

            // Dare
            if ($movimento['totale'] > 0) {
                echo '
        <td class="text-right">
            '.moneyFormat(abs($movimento['totale']), 2).'
        </td>
        <td></td>';
            }

            // Avere
            else {
                echo '
        <td></td>
        <td class="text-right">
            '.moneyFormat(abs($movimento['totale']), 2).'
        </td>';
            }

            echo '
        <td class="text-right">
            '.moneyFormat($scalare, 2).'
        </td>';

            echo '
    </tr>';
        }
    }

    echo '
</table>';
} else {
    echo '
<span>'.tr('Nessun movimento presente').'</span>';
}
