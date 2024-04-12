<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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

$text = [];

if (get('is_pagata') == 'true') {
    $text[] = 'Scadenze pagate';
}
if (get('is_riba') == 'true') {
    $text[] = 'Ri.Ba';
}
if (get('is_cliente') == 'true') {
    $text[] = 'Solo scadenze clienti';
}
if (get('is_fornitore') == 'true') {
    $text[] = 'Solo scadenze fornitori';
}

if (!empty($id_record)) {
    echo '
    <h4><b>'.tr('Scadenza _ANAG_', [
        '_ANAG_' => $records[0]['Anagrafica'],
    ], ['upper' => true]).'</b></h4>';
} elseif (!empty($date_start) and !empty($date_end)) {
    echo '
    <h4><b>'.tr('Scadenze dal _START_ al _END_', [
        '_START_' => Translator::dateToLocale($date_start),
        '_END_' => Translator::dateToLocale($date_end),
    ], ['upper' => true]).'</b>
    </h4>
    <p style="color:#aaa; font-size:10px;" class="text-right">
    '.tr('_TEXT_',
        [
            '_TEXT_' => (!empty($text) ? 'Include: '.implode(', ', $text) : ''),
        ]).'
</p>';
} else {
    echo '
    <h4><b>'.tr('Scadenze').'</b>
    </h4>';
}

echo '
<table class="table table-striped table-bordered" id="contents">
    <thead>
        <tr>
            <th width="10%">'.tr('Documento', [], ['upper' => true]).'</th>
            <th width="30%">'.tr('Anagrafica', [], ['upper' => true]).'</th>
            <th width="30%">'.tr('Tipo di pagamento', [], ['upper' => true]).'</th>
            <th width="10%" class="text-center">'.tr('Data scadenza', [], ['upper' => true]).'</th>
            <th width="10%" class="text-center">'.tr('Importo', [], ['upper' => true]).'</th>
            <th width="10%" class="text-center">'.tr('Già pagato', [], ['upper' => true]).'</th>
        </tr>
    </thead>

    <tbody>';

$somma_imponibile = [];
$somma_sconto = [];
$somma_totale_imponibile = [];
