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

if ($numero != $record['numero']) {
    $different = 1;
}

echo '
<tr>';

echo '
    <td>'.($different ? $record['numero'] : '').'</td>
    <td>'.($different ? Translator::datetoLocale($record['data_registrazione']) : '').'</td>
    <td>'.($different ? $record['numero_esterno'] : '').'</td>
    <td>'.($different ? Translator::datetoLocale($record['data']) : '').'</td>
    <td>'.($different ? $record['codice_tipo_documento_fe'] : '').'</td>
    <td>'.($different ? $record['codice_anagrafica'].' '.safe_truncate(mb_strtoupper(html_entity_decode((string) $record['ragione_sociale']), 'UTF-8'), 50) : '').'</td>
    <td class="text-right">'.moneyFormat($record['totale'], 2).'</td>';

echo '
    <td class="text-right">'.moneyFormat($record['subtotale'], 2).'</td>
    <td class="text-left">'.Translator::numberToLocale($record['percentuale'], 0).'</td>
    <td class="text-left">'.$record['descrizione'].'</td>
    <td class="text-right">'.moneyFormat($record['iva'], 2).'</td>
    </tr>';

$iva[$record['descrizione']][] = $record['iva'];
$totale[$record['descrizione']][] = $record['subtotale'];

$numero = $record['numero'];
$data_registrazione = $record['data_registrazione'];
$numero_esterno = $record['numero'];
$data = $record['data'];
$codice_fe = $record['numero'];
$codice_anagrafica = $record['numero'];

$different = 0;
