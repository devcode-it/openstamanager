<?php

include_once __DIR__.'/../../core.php';

$module_name = 'Scadenzario';
$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

$module = Modules::get('Scadenzario');
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
