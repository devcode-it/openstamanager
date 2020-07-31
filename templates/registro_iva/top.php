<?php

include_once __DIR__.'/../../core.php';

$year_start = date('Y', strtotime($date_start));
$year_end = date('Y', strtotime($date_end));

$esercizio = $year_start == $year_end ? ' - '.tr('Esercizio _YEAR_', [
    '_YEAR_' => $year_end,
]) : '';

if ('entrata' == $dir) {
    $titolo = tr('Registro iva vendita dal _START_ al _END_ _SEZIONALE_', [
        '_START_' => Translator::dateToLocale($date_start),
        '_END_' => Translator::dateToLocale($date_end),
        '_SEZIONALE_' => (!empty($sezionale)) ? ' - '.$sezionale : '',
    ], ['upper' => true]);
} elseif ('uscita' == $dir) {
    $titolo = tr('Registro iva acquisto dal _START_ al _END_ _SEZIONALE_', [
        '_START_' => Translator::dateToLocale($date_start),
        '_END_' => Translator::dateToLocale($date_end),
        '_SEZIONALE_' => (!empty($sezionale)) ? ' - '.$sezionale : '',
    ], ['upper' => true]);
}

$tipo = $dir == 'entrata' ? tr('Cliente') : tr('Fornitore');
$i = 0;
$color = '#dddddd';

echo '<h4><b>'.$titolo.'</b></h4>

<table class="table table-condensed" border="0">
    <thead>
        <tr bgcolor="'.$color.'">
            <th>'.tr('Prot.').'</th>
            <th>'.tr('N<sup>o</sup>&nbsp;doc.').'</th>
            <th>'.tr('Data doc.').'</th>
            <th>'.tr('Data comp.').'</th>
            <th>'.tr('Tipo').'</th>
            <th>'.$tipo.'</th>
            <th>'.tr('Tot. doc.').'</th>
            <th>'.tr('Imponibile').'</th>
            <th>%</th>
            <th>'.tr('Iva').'</th>
            <th>'.tr('Imposta').'</th>
        </tr>
    </thead>

    <tbody>';
