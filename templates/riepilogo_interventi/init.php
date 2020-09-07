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

$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

$module = Modules::get('Interventi');
$id_module = $module['id'];

$total = Util\Query::readQuery($module);

// Lettura parametri modulo
$module_query = $total['query'];

$search_filters = [];

if (is_array($_SESSION['module_'.$id_module])) {
    foreach ($_SESSION['module_'.$id_module] as $field => $value) {
        if (!empty($value) && starts_with($field, 'search_')) {
            $field_name = str_replace('search_', '', $field);
            $field_name = str_replace('__', ' ', $field_name);
            $field_name = str_replace('-', ' ', $field_name);
            array_push($search_filters, '`'.$field_name.'` LIKE "%'.$value.'%"');
        }
    }
}

if (!empty($search_filters)) {
    $module_query = str_replace('2=2', '2=2 AND ('.implode(' AND ', $search_filters).') ', $module_query);
}

// Filtri derivanti dai permessi (eventuali)
$module_query = Modules::replaceAdditionals($id_module, $module_query);

// Scadenze
$records = $dbo->fetchArray($module_query);
