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
use Modules\Anagrafiche\Anagrafica;

$agente = Anagrafica::where([
    ['idanagrafica', '=', $id_record],
])->first();

if (get('is_parz_pagata') == 'true') {
    $text[] = "'Parzialmente pagato'";
}
if (get('is_emessa') == 'true') {
    $text[] = "'Emessa'";
}

echo '
<h4><b>'.tr('Liquidazione provvigioni agente _ANAG_', [
    '_ANAG_' => $agente->ragione_sociale,
], ['upper' => true]).'</b></h4>';

if (!empty($date_start) and !empty($date_end)) {
    echo '
    <h4><b>'.tr('Provvigioni dal _START_ al _END_', [
        '_START_' => Translator::dateToLocale($date_start),
        '_END_' => Translator::dateToLocale($date_end),
    ], ['upper' => true]).'</b>
    </h4>';
} else {
    echo '
    <h5><b>'.tr('Provvigioni').'</b>
    </h5>';
}

echo '
    <p style="color:#aaa; font-size:10px;" class="text-right">
        '.tr('_TEXT_',
    [
        '_TEXT_' => (empty($text) ? 'Solo fatture con stato \'Pagato\'' : 'Include fatture con stato \'Pagato\', '.implode(', ', $text)),
    ]).'
    </p>
<table class="table table-striped table-bordered" id="contents">
    <thead>
        <tr>
            <th width="20%">'.tr('Documento', [], ['upper' => true]).'</th>
            <th width="50%">'.tr('Anagrafica', [], ['upper' => true]).'</th>
            <th width="10%" class="text-center">'.tr('Importo', [], ['upper' => true]).'</th>
            <th width="10%" class="text-center">'.tr('Percentuale', [], ['upper' => true]).'</th>
            <th width="10%" class="text-center">'.tr('Provvigione', [], ['upper' => true]).'</th>
        </tr>
    </thead>

    <tbody>';
