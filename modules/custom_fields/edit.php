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

?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

    <div class="row">
		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "module_id", "values": "query=SELECT id, name as text FROM zz_modules WHERE enabled = 1", "value": "<?php echo $record['id_module']; ?>" ]}
		</div>

        <div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Plugin'); ?>", "name": "plugin_id", "values": "query=SELECT id, name as text FROM zz_plugins WHERE enabled = 1", "value": "<?php echo $record['id_plugin']; ?>" ]}
		</div>
    </div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "name", "required": 1, "value": "$name$" ]}
		</div>

        <div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Nome HTML'); ?>", "name": "html_name", "required": 1, "value": "$html_name$" ]}
		</div>
	</div>

    <div class="row">
		<div class="col-md-6">
			{[ "type": "checkbox", "label": "<?php echo tr('Mostra alla creazione record'); ?>", "name": "on_add","value": "$on_add$" ]}
		</div>

        <div class="col-md-6">
			{[ "type": "checkbox", "label": "<?php echo tr('Mostra di sopra'); ?>", "name": "top", "value": "$top$" ]}
		</div>
	</div>

    <div class="row">
		<div class="col-md-12">
			{[ "type": "textarea", "label": "<?php echo tr('Contenuto'); ?>", "name": "content", "required": 1, "value": "$content$" ]}
		</div>
	</div>
</form>

<?php

include $structure->filepath('content-info.php');

?>

<hr>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
