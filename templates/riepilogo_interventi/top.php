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

$tipo = get('tipo');

$data_inizio = get('data_inizio') ?: ($_SESSION['superselect']['data_inizio'] ?? null);
$data_fine = get('data_fine') ?: ($_SESSION['superselect']['data_fine'] ?? null);

if (!empty($data_inizio) && !formatter()->isStandardDate($data_inizio)) {
    $data_inizio = formatter()->parseDate($data_inizio);
}
if (!empty($data_fine) && !formatter()->isStandardDate($data_fine)) {
    $data_fine = formatter()->parseDate($data_fine);
}

$titolo = tr('Riepilogo attività selezionate', [], ['upper' => true]);
if (!empty($data_inizio) && !empty($data_fine)) {
    $titolo .= ' '.tr('dal _START_ al _END_', [
        '_START_' => dateFormat($data_inizio),
        '_END_' => dateFormat($data_fine),
    ], ['upper' => true]);
} elseif (!empty($data_inizio)) {
    $titolo .= ' '.tr('dal _START_', [
        '_START_' => dateFormat($data_inizio),
    ], ['upper' => true]);
} elseif (!empty($data_fine)) {
    $titolo .= ' '.tr('al _END_', [
        '_END_' => dateFormat($data_fine),
    ], ['upper' => true]);
}

echo '
<br>
<h5 class="text-center"><b>'.$titolo.'</b></h5>

<table class="table">
    <colgroup>
        <col span="2">
        <col style="width:8%">
        <col style="width:8%">
        <col style="width:15%">
        <col style="width:15%">
        <col style="width:15%">
    </colgroup>

    <tbody>';
