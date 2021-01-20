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

echo '
    <tr>
        <td>
            '.$record['Rif. Fattura'].'<br>
            <small>'.Translator::dateToLocale($record['Data emissione']).'</small>
        </td>
        <td>'.$record['Anagrafica'].'</td>
        <td>'.$record['Tipo di pagamento'].'</td>
        <td class="text-center">'.Translator::dateToLocale($record['Data scadenza']).'</td>
        <td class="text-right">'.moneyFormat($record['Importo'], 2).'</td>
        <td class="text-right">'.moneyFormat($record['Pagato'], 2).'</td>
    </tr>';
