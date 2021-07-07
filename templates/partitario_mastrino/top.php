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
<table class="table table-striped table-bordered" id="contents">
    <thead>
        <tr>
            <th width="10%">DATA</th>
            <th width="51%">DESCRIZIONE</th>
            <th width="13%">DARE</th>
            <th width="13%">AVERE</th>
            <th width="13%">SCALARE</th>
        </tr>
    </thead>
    <tbody>';

    $scalare = 0;
