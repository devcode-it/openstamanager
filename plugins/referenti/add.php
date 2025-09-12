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
use Models\Module;

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
    <input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="addreferente">

	<!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="0">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Nominativo').'", "name": "nome", "required": 1 ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Mansione').'", "name": "idmansione", "ajax-source": "mansioni", "required": 1, "icon-after": "add|'.Module::where('name', 'Mansioni referenti')->first()->id.'" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "telefono", "label": "'.tr('Telefono').'", "name": "telefono" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "email", "label": "'.tr('Indirizzo email').'", "name": "email", "validation": "email"]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede", "values": "query=SELECT 0 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT_WS(\' - \', nomesede, citta) AS descrizione FROM an_sedi WHERE idanagrafica='.$id_parent.'", "value": "0", "icon-after": "add|'.$id_module.'|id_plugin='.$id_plugin_sedi.'&id_parent='.$id_parent.'" ]}
		</div>

       <div class="col-md-6">
            {[ "type": "checkbox", "label": "'.tr('Opt-in per newsletter').'", "name": "enable_newsletter_add", "id": "enable_newsletter_m", "value": "1",  "help": "'.tr("Blocco per l'invio delle email.").'" ]}
        </div>
	</div>

	<!-- PULSANTI -->
	<div class="modal-footer">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';
