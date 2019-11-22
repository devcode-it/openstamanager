<?php

$orientation = (isset($_SESSION['settings']['orientation'])) ? $_SESSION['settings']['orientation'] : 'L';
$format = (isset($_SESSION['dashboard']['format'])) ? $_SESSION['dashboard']['format'] : 'A4';
$orientation = (isset($_SESSION['dashboard']['orientation'])) ? $_SESSION['dashboard']['orientation'] : 'L';

return [
    'format' => $format,
    'orientation' => $orientation,
];
