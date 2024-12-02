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

use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;

$rs = Intervento::where('idstatointervento', '=', Stato::where('codice', '=', 'WIP')->first()->id)->get()->toArray();

if (!empty($rs)) {
    echo '
<table class="table table-hover">
    <tr>
        <th width="50%">'.tr('Attività').'</th>
        <th width="15%" class="text-center">'.tr('Data richiesta').'</th>
    </tr>';

    foreach ($rs as $r) {
        $data_richiesta = $r['data_richiesta'] ? date('d/m/Y', strtotime((string) $r['data_richiesta'])) : '';

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
