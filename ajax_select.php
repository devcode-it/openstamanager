<?php

include_once __DIR__.'/core.php';

if (!isset($resource)) {
    $op = empty($op) ? filter('op') : $op;
    $search = filter('search');
    $page = filter('page') ?: 0;
    $length = filter('length') ?: 100;

    if (!isset($elements)) {
        $elements = [];
    }
    $elements = (!is_array($elements)) ? explode(',', $elements) : $elements;

    $results = AJAX::select($op, $elements, $search, $page, $length);

    echo json_encode($results);
}

// Casi particolari
else {
}
