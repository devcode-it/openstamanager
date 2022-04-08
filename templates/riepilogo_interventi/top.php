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
 
$tipo = get('tipo');

echo '
<h4><b>'.tr('Riepilogo attività dal _START_ al _END_', [
    '_START_' => Translator::dateToLocale($date_start),
    '_END_' => Translator::dateToLocale($date_end),
    ], ['upper' => true]).'</b></h4>

<table class="table table-bordered">
    <thead>
        <tr>
            <th colspan="2"><small>'.tr('Documento', [], ['upper' => true]).'</small></th>
            <th class="text-center" style="width:10%"><small>'.tr('Ore', [], ['upper' => true]).'</small></th>
            <th class="text-center" style="width:15%"><small>'.($tipo=='interno' ? tr('Costo totale', [], ['upper' => true]) : tr('Imponibile', [], ['upper' => true])).'</th>
            <th class="text-center" style="width:15%"><small>'.tr('Sconto', [], ['upper' => true]).'</small></th>
            <th class="text-center" style="width:15%"><small>'.($tipo=='interno' ? tr('Costo netto', [], ['upper' => true]) : tr('Totale imponibile', [], ['upper' => true])).'</small></th>
        </tr>
    </thead>

    <tbody>';
