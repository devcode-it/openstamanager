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
use Models\Module;
use Models\PrintTemplate;

$id_record = filter('id_record');
$dir = filter('dir');
$nome_stampa = filter('nome_stampa');
$id_print = PrintTemplate::where('name', $nome_stampa)->first()->id;
$id_module = Module::where('name', 'Stampe contabili')->first()->id;

$year = (new Carbon($_SESSION['period_end']))->format('Y');

// Trovo id_print della stampa
$link = Prints::getHref($nome_stampa, $id_record);

echo '
<div class="alert alert-info hidden" id="period">
    <i class="fa fa-exclamation-circle"></i> '.tr('Non è possibile creare la stampa definitiva nel periodo selezionato, è necessario prima impostare un periodo!').'
</div>

<form action="" method="post" id="form" >
	<div class="row">';
echo '
		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data inizio').'", "required": "1", "name": "date_start", "value": "'.$_SESSION['period_start'].'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data fine').'", "required": "1", "name": "date_end", "value": "'.$_SESSION['period_end'].'" ]}
		</div>

		<div class="col-md-4">
		{[ "type": "checkbox", "label": "'.tr('Includi emesse').'", "name": "is_emessa" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Formato').'", "name": "format", "required": "1", "values": "list=\"A4\": \"'.tr('A4').'\", \"A3\": \"'.tr('A3').'\"", "value": "'.$_SESSION['stampe_contabili']['format'].'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Orientamento').'", "name": "orientation", "required": "1", "values": "list=\"L\": \"'.tr('Orizzontale').'\", \"P\": \"'.tr('Verticale').'\"", "value": "'.$_SESSION['stampe_contabili']['orientation'].'" ]}
		</div> 
		<div class="col-md-4">
		{[ "type": "checkbox", "label": "'.tr('Includi parzialmente pagate').'", "name": "is_parz_pagata" ]}
		</div>
';

echo '
		<div class="col-md-4 pull-right">
			<p style="line-height:14px;">&nbsp;</p>
			<button type="button" class="btn btn-primary btn-block" onclick="if($(\'#form\').parsley().validate()) { return avvia_stampa(); }">
				<i class="fa fa-print"></i> '.tr('Stampa').'
			</button>
		</div>
	</div>
</form>
<br>';

echo '
<script>
	$(document).ready(init);

	function avvia_stampa (){
			var is_emessa = $("#is_emessa").is(":checked");
			var is_parz_pagata = $("#is_parz_pagata").is(":checked");
			window.open("'.$link.'&dir='.$dir.'&is_emessa="+is_emessa+"&is_parz_pagata="+is_parz_pagata+"&notdefinitiva=1&id_sezionale="+$("#id_sezionale").val()+"&date_start="+$("#date_start").val()+"&date_end="+$("#date_end").val()+"");
			$("#modals > div").modal("hide");
		
	}

	$("#format").change(function() {
		session_set("stampe_contabili,format", $(this).val(), 0, 0);
	});

	$("#orientation").change(function() {
		session_set("stampe_contabili,orientation", $(this).val(), 0, 0);
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
		}';
if ($nome_stampa != 'Liquidazione IVA') {
    echo 'eseguiControlli();';
}
echo '
	});
</script>';
