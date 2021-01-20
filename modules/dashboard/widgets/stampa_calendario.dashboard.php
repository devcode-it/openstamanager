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

use Carbon\Carbon;

// Trovo id_print della stampa
$id_print = Prints::getModulePredefinedPrint('Dashboard')['id'];
$date = new Carbon($_SESSION['dashboard']['date']);

echo '
<form action="" method="post" onsubmit="if($(this).parsley().validate()) { return stampa_calendario(); }" >

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Mese e anno').'", "name": "date", "required": "1" ]}
		</div>

		<div class="col-md-2">
			{[ "type": "select", "label": "'.tr('Formato').'", "name": "format", "required": "1", "values": "list=\"A4\": \"'.tr('A4').'\", \"A3\": \"'.tr('A3').'\"", "value": "'.$_SESSION['dashboard']['format'].'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Orientamento').'", "name": "orientation", "required": "1", "values": "list=\"L\": \"'.tr('Orizzontale').'\", \"P\": \"'.tr('Verticale').'\"", "value": "'.$_SESSION['dashboard']['orientation'].'" ]}
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

$(function() {
    $("#date").datetimepicker({
        format: "MMMM YYYY",
        locale: globals.locale,
        useCurrent: false,
        defaultDate: moment("'.$date->format('Y-m-d H:i:s').'")
    });

    $("#date").on("dp.change", function(e) {
        session_set("dashboard,date", e.date.format("YYYY-MM-DD"), 0, 0);
    });
});
</script>';
