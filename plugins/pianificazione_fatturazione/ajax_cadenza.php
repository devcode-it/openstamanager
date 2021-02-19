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

use Carbon\Carbon;
use Modules\Contratti\Contratto;

include_once __DIR__.'/../../core.php';

$contratto = Contratto::find($id_record);

if (get('scadenza') == 'Mensile') {
    $timeing = '+1 month';
}
if (get('scadenza') == 'Bimestrale') {
    $timeing = '+2 month';
}
if (get('scadenza') == 'Trimestrale') {
    $timeing = '+3 month';
}
if (get('scadenza') == 'Quadrimestrale') {
    $timeing = '+4 month';
}
if (get('scadenza') == 'Semestrale') {
    $timeing = '+6 month';
}
if (get('scadenza') == 'Annuale') {
    $timeing = '+12 month';
}

$data_inizio = new Carbon(get('data_inizio'));

echo '
<div class="row" id="ajax_cadenza">';

    $data_corrente = $data_inizio->startOfMonth();
    $data_conclusione = $contratto->data_conclusione;
    $count = 0;

    while ($data_corrente->lessThanOrEqualTo($data_conclusione)) {
        $data = $data_corrente->endOfMonth()->format('Y-m-d');
        $data_fatturazione = ($data_fatturazione ?: date('Y-m', strtotime($data)));
        unset($checked);

        if ($id_module == Modules::get('Contratti')['id']) {
            if ($data == date('Y-m-t', strtotime($timeing, strtotime($data_fatturazione)))) {
                $checked = 'checked';
                $data_fatturazione = date('Y-m', strtotime($data));
            }
        }

        echo '
        <div class="col-md-3">
            <label for="m_'.$count.'">
                <input type="checkbox" class="unblockable" id="m_'.$count.'" name="selezione_periodo['.$count.']" '.$checked.' />
                '.ucfirst($data_corrente->formatLocalized('%B %Y')).'
            </label>
            <input type="hidden" name="periodo['.$count.']" value="'.$data.'">
        </div>';

        $data_corrente = $data_corrente->addDay();
        ++$count;
    }

    echo '
</div>

<script>
    $(document).ready(function(){
        var check = 0;
        $("#periodi input").each(function (){
            if( $(this).is(":checked") ){
                check = check + 1;
            }
        });
        $("#total_check").html("Rate: " + check).trigger("change");
    });
</script>';
