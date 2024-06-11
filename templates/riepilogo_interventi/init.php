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
use Models\Module;

$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

$module = Module::where('name', 'Interventi')->first();

$total = Util\Query::readQuery($module);

// Lettura parametri modulo
$module_query = $total['query'];

$selected = [];

if (is_array($_SESSION['superselect']['interventi'])) {
    foreach ($_SESSION['superselect']['interventi'] as $value) {
        array_push($selected, '`id` = "'.$value.'"');
    }
}

if (!empty($selected)) {
    $module_query = str_replace('2=2', '2=2 AND ('.implode(' OR ', $selected).') ', $module_query);
}

// Filtri derivanti dai permessi (eventuali)
$module_query = Modules::replaceAdditionals($module->id, $module_query);

// Scadenze
$records = $dbo->fetchArray($module_query);
