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

include_once __DIR__.'/../../../core.php';

// Trovo id_print della stampa
$id_print = Prints::getPrints()['Stampa calendario settimanale'];

echo '
<form action="" method="post" onsubmit="if($(this).parsley().validate()) { return stampa_calendario(); }" >

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Settimana').'", "name": "date", "required": "1" ]}
		</div>

		<div class="col-md-2">
			{[ "type": "select", "label": "'.tr('Formato').'", "name": "format", "required": "1", "values": "list=\"A4\": \"'.tr('A4').'\", \"A3\": \"'.tr('A3').'\"", "value": "'.$_SESSION['dashboard']['format'].'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Orientamento').'", "name": "orientation", "required": "1", "values": "list=\"L\": \"'.tr('Orizzontale').'\"", "value": "'.$_SESSION['dashboard']['orientation'].'" ]}
		</div>


		<div class="col-md-2">
			<p style="line-height:14px;">&nbsp;</p>

			<button type="submit" class="btn btn-primary btn-block">
			<i class="fa fa-print"></i> '.tr('Stampa').'
			</button>
		</div>

	</div>

</form>

<script>$(document).ready(init)</script>';

echo '
<script>
function stampa_calendario (){
	window.open(globals.rootdir + "/pdfgen.php?id_print='.$id_print.'");

	return false;
}

$("#format").change(function() {
	session_set("dashboard,format", $(this).val(), 0, 0);
});

$("#orientation").change(function() {
	session_set("dashboard,orientation", $(this).val(), 0, 0);
});
</script>';

?>

<style>

.bootstrap-datetimepicker-widget tr:hover {
    background-color: #eeeeee;
}

</style>

<script>

$(function() {

	moment.updateLocale('en', {
		week: { dow: 1 } // Monday is the first day of the week
	});

	moment.updateLocale('it', {
		week: { dow: 1 } // Monday is the first day of the week
	});

	$("#date").datetimepicker({
		format: 'DD/MM/YYYY',
	});
	
	//Get the value of Start and End of Week
	$('#date').on('dp.change', function (e) {
		value = $("#date").val();
		firstDate = moment(value, "DD/MM/YYYY").day(1).format("DD/MM/YYYY");
		lastDate =  moment(value, "DD/MM/YYYY").day(7).format("DD/MM/YYYY");
		$("#date").val(firstDate + "  -  " + lastDate);

		session_set("dashboard,date_week_start", moment(firstDate, "DD/MM/YYYY").format("YYYY-MM-DD"), 0, 0);
		session_set("dashboard,date_week_end", moment(lastDate, "DD/MM/YYYY").format("YYYY-MM-DD"), 0, 0);

	});
});

</script>
