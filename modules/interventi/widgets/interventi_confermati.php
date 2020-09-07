<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

$rs = $dbo->fetchArray('SELECT * FROM in_interventi WHERE in_interventi.idstatointervento = (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.codice=\'WIP\') ORDER BY data_richiesta ASC');

if (!empty($rs)) {
    echo '
<table class="table table-hover">
    <tr>
        <th width="50%">'.tr('Attività').'</th>
        <th width="15%" class="text-center">'.tr('Data richiesta').'</th>
    </tr>';

    foreach ($rs as $r) {
        $data_richiesta = !empty($r['data_richiesta']) ? Translator::dateToLocale($r['data_richiesta']) : '';

        echo '
    <tr >
        <td>
            '.Modules::link('Interventi', $r['id'], 'Intervento n. '.$r['codice'].' del '.$data_richiesta).'<br>
            <small class="help-block">'.$r['ragione_sociale'].'</small>
        </td>
        <td class="text-center">'.$data_richiesta.'</td>
    </tr>';
    }
    echo '
</table>';
} else {
    echo '
<p>'.tr('Non ci sono attività programmate').'.</p>';
}
