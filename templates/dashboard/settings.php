<?php

$format = (isset($_SESSION['settings']['format'])) ? $_SESSION['settings']['format'] : 'A4';
$orientation = (isset($_SESSION['settings']['orientation'])) ? $_SESSION['settings']['orientation'] : 'L';

return [
    'format' => $format,
    'orientation' => $orientation,
    // 'header-height' => 0,
];
