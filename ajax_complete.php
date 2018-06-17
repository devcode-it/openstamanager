<?php

include_once __DIR__.'/core.php';

if (!isset($resource)) {
    $module = $get['module'];
    $op = $get['op'];

    $result = AJAX::complete($op);

    echo $result;
}

// Casi particolari
else {
}
