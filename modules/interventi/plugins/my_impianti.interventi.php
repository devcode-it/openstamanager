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

use Modules\Interventi\Intervento;

include_once __DIR__.'/../../../core.php';

// INTERVENTI ESEGUITI SU QUESTO IMPIANTO
echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Interventi eseguiti su questo impianto').'</h3>
    </div>
    <div class="box-body">';

$results = $dbo->fetchArray('SELECT in_interventi.id, in_interventi.codice, descrizione, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=my_impianti_interventi.idintervento) AS data FROM my_impianti_interventi INNER JOIN in_interventi ON my_impianti_interventi.idintervento=in_interventi.id WHERE idimpianto='.prepare($id_record).' ORDER BY data DESC');
$totale_interventi = 0;

if (!empty($results)) {
    echo '
        <table class="table table-striped table-hover">
            <tr>
                <th width="350">'.tr('Intervento').'</th>
                <th>'.tr('Descrizione').'</th>
                <th width="150" class="text-right">'.tr('Costo totale').'</th>
            </tr>';

    foreach ($results as $result) {
        $intervento = Intervento::find($result['id']);
        $totale_interventi += $intervento->totale;

        echo '
            <tr>
                <td>
                    '.Modules::link('Interventi', $result['id'], $intervento->getReference()).'
                </td>
                <td>'.nl2br($result['descrizione']).'</td>
                <td class="text-right">'.moneyFormat($intervento->totale).'</td>
            </tr>';
    }

    echo '  <tr>
                <td colspan="2" class="text-right">
                    <b>'.tr('Totale').':</b>
                </td>
                <td class="text-right">
                    <b>'.moneyFormat($totale_interventi).'</b>
                </td>
            </tr>';

    echo '
        </table>';
} else {
    echo '
<div class=\'alert alert-info\' ><i class=\'fa fa-info-circle\'></i> '.tr('Nessun intervento su questo impianto').'.</div>';
}

echo '
    </div>
</div>';
