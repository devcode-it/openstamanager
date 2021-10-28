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

// Trovo id_print della stampa
$link = Prints::getHref('Bilancio', null);

echo '
<form action="" method="post" onsubmit="if($(this).parsley().validate()) { return avvia_stampa(); }" >

	<div class="row">
		<div class="col-md-6">
			{[ "type": "checkbox", "label": "'.tr('Elenco analitico delle anagrafiche').'", "name": "elenco_analitico", "value": "0" ]}
		</div>

	</div>

	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary">
				<i class="fa fa-print"></i> '.tr('Stampa Bilancio').'
			</button>
		</div>
	</div>

</form>

<script>$(document).ready(init)</script>';

echo '
<script>
	function avvia_stampa (){
		var elenco = 0;
		if($("#elenco_analitico").is(":checked")){
			elenco = 1;
		} 
		window.open("'.$link.'&elenco_analitico="+elenco+"");
	return false;
	}
</script>';
