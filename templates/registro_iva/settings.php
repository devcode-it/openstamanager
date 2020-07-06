<?php

$format = (isset($_SESSION['stampe_contabili']['format'])) ? $_SESSION['stampe_contabili']['format'] : 'A4';
$orientation = (isset($_SESSION['stampe_contabili']['orientation'])) ? $_SESSION['stampe_contabili']['orientation'] : 'L';

return [
    'format' => $format,
    'orientation' => $orientation,
    'font-size' => '11pt',
];
