<?php

include_once __DIR__.'/core.php';

if (!isset($resource)) {
    $op = empty($op) ? filter('op') : $op;
    $search = filter('q');

    if (!isset($elements)) {
        $elements = [];
    }
    $elements = (!is_array($elements)) ? explode(',', $elements) : $elements;

    $results = AJAX::select($op, $elements, $search);

    echo json_encode($results);
}

// Casi particolari
else {
}
