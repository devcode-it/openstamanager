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

use Carbon\Carbon;

// Trovo id_print della stampa
$link = Prints::getHref('Scadenzario', null);

$year = (new Carbon($_SESSION['period_end']))->format('Y');
$periodi[] = [
    'id' => 'manuale',
    'text' => tr('Manuale'),
];

$month_start = 1;
$month_end = 3;
for ($i = 1; $i <= 4; ++$i) {
    $periodi[] = [
        'id' => ''.$i.'_trimestre',
        'text' => tr('_NUM_° Trimestre _YEAR_', ['_NUM_' => $i, '_YEAR_' => $year]),
        'date_start' => $year.','.$month_start.',01',
        'date_end' => $year.','.$month_end.','.(new Carbon($year.'-'.$month_end.'-01'))->endOfMonth()->format('d'),
    ];
    $month_start += 3;
    $month_end += 3;
}

for ($i = 1; $i <= 12; ++$i) {
    $month = (new Carbon($year.'-'.$i.'-01'))->locale('it')->getTranslatedMonthName('IT MMMM');
    $periodi[] = [
        'id' => ''.$i.'_mese',
        'text' => tr('_MONTH_ _YEAR_', ['_MONTH_' => $month, '_YEAR_' => $year]),
        'date_start' => $year.','.$i.',01',
        'date_end' => $year.','.$i.','.(new Carbon($year.'-'.$i.'-01'))->endOfMonth()->format('d'),
    ];
}

echo '
	<div class="row">
        <div class="col-md-3">
			{[ "type": "date", "label": "'.tr('Data inizio').'", "name": "date_start", "value": "'.$_SESSION['period_start'].'" ]}
		</div>
        <div class="col-md-3">
			{[ "type": "date", "label": "'.tr('Data fine').'", "name": "date_end", "value": "'.$_SESSION['period_end'].'" ]}
		</div>
        <div class="col-md-3">
			{[ "type": "select", "label": "'.tr('Periodo').'", "name": "periodo", "required": "1", "values": '.json_encode($periodi).', "value": "manuale" ]}
		</div>
        <div class="col-md-3">
        {[ "type": "select", "label": "'.tr('Anagrafica').'", "name": "id_anagrafica", "values": "'.$id_anagrafica.'", "ajax-source": "anagrafiche" ]}
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
			{[ "type": "checkbox", "label": "'.tr('Includi scadenze pagate').'", "name": "is_pagata" ]}
		</div>
        <div class="col-md-3">
            {[ "type": "checkbox", "label": "'.tr('Includi solo Ri.Ba').'", "name": "is_riba" ]}
        </div>
        <div class="col-md-3">
            {[ "type": "checkbox", "label": "'.tr('Includi solo scadenze Clienti').'", "name": "is_cliente" ]}
        </div>
        <div class="col-md-3">
            {[ "type": "checkbox", "label": "'.tr('Includi solo scadenze Fornitori').'", "name": "is_fornitore" ]}
        </div>
	</div>

	<div class="row">
		<div class="col-md-12 text-right">
			<button type="button" onclick="avvia_stampa();" class="btn btn-primary">
				<i class="fa fa-print"></i> '.tr('Stampa scadenzario').'
			</button>
		</div>
	</div>

<script>$(document).ready(init)</script>';

echo '
<script>
    $(document).ready(init);

	function avvia_stampa (){
        var date_start = $("#date_start").val();
        var date_end = $("#date_end").val();
        var id_anagrafica = $("#id_anagrafica").val();
        var is_pagata = $("#is_pagata").is(":checked");
        var is_riba = $("#is_riba").is(":checked");
        var is_cliente = $("#is_cliente").is(":checked");
        var is_fornitore = $("#is_fornitore").is(":checked");
        
        window.open("'.$link.'&date_start="+date_start+"&date_end="+date_end+"&is_pagata="+is_pagata+"&is_riba="+is_riba+"&is_cliente="+is_cliente+"&is_fornitore="+is_fornitore+"&id_anagrafica="+id_anagrafica, "_blank");
	}

    $("#is_cliente").change(function() {
        if($(this).is(":checked")) {
            $("#is_fornitore").prop("checked", false);
        }
    });
    
    $("#is_fornitore").change(function() {
        if($(this).is(":checked")) {
            $("#is_cliente").prop("checked", false);
        }
    });

    $("#periodo").change(function() {
		if ($(this).val()=="manuale") {
			input("date_start").enable();
			input("date_end").enable();
		} else {
			$("#date_start").data("DateTimePicker").date(new Date(input("periodo").getData().date_start));
			$("#date_end").data("DateTimePicker").date(new Date(input("periodo").getData().date_end));
			input("date_start").disable();
			input("date_end").disable();
		}
	});
</script>';
