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
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="updatereferente">

	<div class="row">
        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Nominativo').'", "name": "nome", "required": 1, "value" : "$nome$" ]}
        </div>

        <div class="col-md-6">
        {[ "type": "select", "label": "'.tr('Mansione').'", "name": "idmansione", "ajax-source": "mansioni", "required": 1, "value" : "$idmansione$", "icon-after": "add|'.Module::where('name', 'Mansioni referenti')->first()->id.'" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "telefono", "label": "'.tr('Telefono').'", "name": "telefono", "value" : "$telefono$" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "email", "label": "'.tr('Indirizzo email').'", "name": "email", "value" : "$email$", "validation": "email" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede", "values": "query=SELECT 0 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT_WS(\' - \', nomesede, citta) AS descrizione FROM an_sedi WHERE idanagrafica='.$id_parent.'", "value" : "$idsede$", "icon-after": "add|'.$id_module.'|id_plugin='.$id_plugin_sedi.'&id_parent='.$id_parent.'" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "checkbox", "label": "'.tr('Opt-out per newsletter').'", "name": "disable_newsletter", "id": "disable_newsletter_m", "value": "'.empty($record['enable_newsletter']).'", "help": "'.tr('Blocco per l\'invio delle email.').'" ]}
        </div>
    </div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
            <a class="btn btn-danger ask" data-backto="record-edit" data-op="deletereferente" data-id_record="'.$record['id'].'" data-id_plugin="'.$id_plugin.'" data-id_module="'.$id_module.'" data-id_parent="'.$id_parent.'">
                <i class="fa fa-trash"></i> '.tr('Elimina').'
            </a>

			<button type="submit" class="btn btn-success pull-right"><i class="fa fa-check"></i> '.tr('Salva').'</button>
		</div>
	</div>
</form>';
