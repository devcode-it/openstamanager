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

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="updatecontratto">

	<div class="row">
        <div class="col-md-6">
            '.Modules::link('Contratti', $record['id'], null, null, 'class="pull-right"').'
            {[ "type": "select", "label": "'.tr('Contratto').'", "name": "idcontratto", "value": "$id$", "ajax-source": "contratti", "disabled": "1" ]}
        </div>
        <div class="col-md-6">
            {[ "type": "checkbox", "label": "'.tr('Predefinito').'", "disabled": "'.(intval($record['is_pianificabile']) ? '0' : '1').'", "name": "predefined", "value" : "$predefined$", "help": "'.tr('Il contratto predefinito, se lavorabile, verrà selezionato automaticamente in fase di apertura attività per il cliente').'", "values": "'.tr('Contratto impostato come predefinito,Contratto non predefinito').'" ]}
        </div>
    </div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
			<button type="submit" class="btn btn-success pull-right"><i class="fa fa-check"></i> '.tr('Salva').'</button>
		</div>
	</div>
</form>';
