<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

$id_conto = get('id');

// Informazioni sul conto
$query = 'SELECT *, idpianodeiconti2 AS idpianodeiconti FROM co_pianodeiconti3 WHERE id='.prepare($id_conto);
$conto = $dbo->fetchOne($query);

echo '
<p>'.tr('Seleziona il periodo temporale per il quale desideri aggiornare la percentuale di deducibilità del conto "_DESC_". La nuova percentuale è: _PERC_%', [
        '_DESC_' => $conto['descrizione'],
        '_PERC_' => numberFormat($conto['percentuale_deducibile'], 0),
    ]).'.</p>
<form action="" method="post">
    <input type="hidden" name="op" value="aggiorna_reddito">
    <input type="hidden" name="backto" value="record-list">
    <input type="hidden" name="id_conto" value="'.$conto['id'].'">

    <div class="row">
        <div class="col-md-6">
            {[ "type": "date", "label": "'.tr('Inizio del periodo').'", "name": "start", "required": 1, "value": "'.$_SESSION['period_start'].'" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "date", "label": "'.tr('Fine del periodo').'", "name": "end", "required": 1, "value": "'.$_SESSION['period_end'].'" ]}
        </div>
    </div>

    <div class="pull-right">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-refresh"></i> '.tr('Aggiorna').'
        </button>
    </div>
    <div class="clearfix"></div>
</form>

<script>$(document).ready(init)</script>';
