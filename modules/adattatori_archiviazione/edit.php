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

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="dir" value="<?php echo $dir; ?>">

	<div class="row">
		<div class="col-md-5">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "name", "value": "$name$", "required": 1 ]}
		</div>

        <div class="col-md-5">
			{[ "type": "select", "label": "<?php echo tr('Tipo archiviazione'); ?>", "name": "class", "value": "<?php echo explode('\\', $record['class'])[4]; ?>", "values": "list=\"LocalAdapter\":\"Archiviazione locale\",\"FTPAdapter\":\"Archiviazione FTP\"", "required": 1, "disabled": "1" ]}
		</div>

        <div class="col-md-2">
			{[ "type": "checkbox", "label": "<?php echo tr('Predefinito'); ?>", "name": "is_default", "value": "$is_default$", "extra": "<?php echo $record['is_default'] == 1 ? 'readonly' : ''; ?>" ]}
		</div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "<?php echo tr('Opzioni'); ?>", "name": "options", "value": "$options$", "required": 0, "help": "Specifica le opzioni per il tipo di archiviazione in formato JSON" ]}
        </div>
    </div>

    <!-- PULSANTI -->
    <?php

    $rs_files = Models\Upload::where('id_adapter', $record['id'])->get();

if (sizeof($rs_files) > 0) {
    echo '
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning">L\'adattatore non può essere eliminato perchè uno o più file sono collegati.</div>
        </div>
    </div>';
} elseif ($adapter->is_default && $adapter->can_delete) {
    echo '
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning">L\'adattatore non può essere eliminato.</div>
        </div>
    </div>';
} else {
    echo '
    <a class="btn btn-danger ask" data-backto="record-list">
        <i id ="elimina" class="fa fa-trash"></i> <?php echo tr(\'Elimina\'); ?>
    </a>';
}
?>

</form>