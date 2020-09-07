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

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
    <input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="addreferente">

    <!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Nominativo').'", "name": "nome", "required": 1 ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Mansione').'", "name": "mansione", "required": 1 ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Telefono').'", "name": "telefono" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Indirizzo email').'", "name": "email" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			{[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede", "values": "query=SELECT 0 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT_WS(\' - \', nomesede, citta) AS descrizione FROM an_sedi WHERE idanagrafica='.$id_parent.'", "value": "0", "required": 1 ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';
?>
<script>
    $(document).ready( function(){
        $(document).load("ajax_complete.php?op=get_mansioni", function(response){
            $("#mansione").autocomplete({source: response.split("|")});
        } );
    });
</script>
