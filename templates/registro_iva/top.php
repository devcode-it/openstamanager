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

$year_start = date('Y', strtotime($date_start));
$year_end = date('Y', strtotime($date_end));

$esercizio = $year_start == $year_end ? ' - '.tr('Esercizio _YEAR_', [
    '_YEAR_' => $year_end,
]) : '';

if ('entrata' == $dir) {
    $titolo = tr('Registro iva vendita dal _START_ al _END_ _SEZIONALE_', [
        '_START_' => Translator::dateToLocale($date_start),
        '_END_' => Translator::dateToLocale($date_end),
        '_SEZIONALE_' => (!empty($sezionale)) ? ' - '.$sezionale : '',
    ], ['upper' => true]);
} elseif ('uscita' == $dir) {
    $titolo = tr('Registro iva acquisto dal _START_ al _END_ _SEZIONALE_', [
        '_START_' => Translator::dateToLocale($date_start),
        '_END_' => Translator::dateToLocale($date_end),
        '_SEZIONALE_' => (!empty($sezionale)) ? ' - '.$sezionale : '',
    ], ['upper' => true]);
}

$tipo = $dir == 'entrata' ? tr('Cliente') : tr('Fornitore');
$i = 0;
$color = '#dddddd';

echo '<h4><b>'.$titolo.'</b></h4>

<table class="table table-condensed" border="0">
    <thead>
        <tr bgcolor="'.$color.'">
            <th>'.tr('Prot.').'</th>
            <th>'.tr('N<sup>o</sup>&nbsp;doc.').'</th>
            <th>'.tr('Data doc.').'</th>
            <th>'.tr('Data comp.').'</th>
            <th>'.tr('Tipo').'</th>
            <th>'.$tipo.'</th>
            <th>'.tr('Tot. doc.').'</th>
            <th>'.tr('Imponibile').'</th>
            <th>%</th>
            <th>'.tr('Iva').'</th>
            <th>'.tr('Imposta').'</th>
        </tr>
    </thead>

    <tbody>';
