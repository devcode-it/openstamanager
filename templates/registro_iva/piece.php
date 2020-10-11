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

include_once __DIR__.'/../../core.php';

echo '
<tr>';

echo '
    <td>'.str_pad($record['idmovimenti'], 8, "0", STR_PAD_LEFT).'</td>
    <td>'.Translator::datetoLocale($record['data_competenza']).'</td>
    <td>'.$record['numero_esterno'].'</td>
    <td>'.Translator::datetoLocale($record['data']).'</td>
    <td>'.$record['codice_tipo_documento_fe'].'</td>
    <td>'.$record['codice_anagrafica'].' / '.safe_truncate(mb_strtoupper(html_entity_decode($record['ragione_sociale']), 'UTF-8'), 50).'</td>
    <td class="text-right">'.moneyFormat($record['totale']).'</td>';

echo '
    <td class="text-right">'.moneyFormat($record['subtotale']).'</td>
    <td class="text-left">'.Translator::numberToLocale($record['percentuale'], 0).'</td>
    <td class="text-left">'.$record['desc_iva'].'</td>
    <td class="text-right">'.moneyFormat($record['iva']).'</td>
    </tr>';

$iva[$record['desc_iva']][] = $record['iva'];
$totale[$record['desc_iva']][] = $record['subtotale'];
