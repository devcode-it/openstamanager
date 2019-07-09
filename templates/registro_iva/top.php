<?php

include_once __DIR__.'/../../core.php';

$year_start = date('Y', strtotime($date_start));
$year_end = date('Y', strtotime($date_end));

$esercizio = $year_start == $year_end ? ' - '.tr('Esercizio _YEAR_',[
    '_YEAR_' => $year_end,
]) : '';

if ('entrata' == $dir) {
    $titolo = tr('Registro iva vendita dal _START_ al _END_', [
        '_START_' => Translator::dateToLocale($date_start),
        '_END_' => Translator::dateToLocale($date_end),
    ], ['upper' => true]);
} elseif ('uscita' == $dir) {
    $titolo = tr('Registro iva acquisto dal _START_ al _END_', [
        '_START_' => Translator::dateToLocale($date_start),
        '_END_' => Translator::dateToLocale($date_end),
    ], ['upper' => true]);
}

$tipo = $dir == "entrata" ? tr("Cliente") : tr("Fornitore");

echo '<h4><b>'.$titolo.'</b></h4>

<table class="table">
    <thead>
        <tr bgcolor="#dddddd">
            <th>'.tr('N<sup>o</sup> prot.').'</th>
            <th>'.tr('N<sup>o</sup> doc.').'</th>
            <th>'.tr('Data').'</th>
            <th>'.tr('Tipo').'</th>
            <th>'.$tipo.'</th>
            <th>'.tr('Tot doc.').'</th>
            <th>'.tr('Imponibile').'</th>
            <th>%</th>
            <th>'.tr('Iva').'</th>
            <th>'.tr('Imposta').'</th>
        </tr>
    </thead>

    <tbody>';
