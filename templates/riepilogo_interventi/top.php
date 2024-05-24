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
<br>
<h5 class="text-center"><b>'.tr('Riepilogo attività selezionate', [], ['upper' => true]).'</b></h5>

<table class="table border-bottom">
    <thead>
        <tr>
            <th colspan="2" class="text-muted"><small>'.tr('Documento', [], ['upper' => true]).'</small></th>
            <th class="text-center text-muted" style="width:8%"><small>'.tr('KM', [], ['upper' => true]).'</small></th>
            <th class="text-center text-muted" style="width:8%"><small>'.tr('Ore', [], ['upper' => true]).'</small></th>
            <th class="text-center text-muted" style="width:15%"><small>'.($tipo == 'interno' ? tr('Costo totale', [], ['upper' => true]) : tr('Imponibile', [], ['upper' => true])).'</th>
            <th class="text-center text-muted" style="width:15%"><small>'.tr('Sconto', [], ['upper' => true]).'</small></th>
            <th class="text-center text-muted" style="width:15%"><small>'.($tipo == 'interno' ? tr('Costo netto', [], ['upper' => true]) : tr('Totale imponibile', [], ['upper' => true])).'</small></th>
        </tr>
    </thead>

    <tbody>';
