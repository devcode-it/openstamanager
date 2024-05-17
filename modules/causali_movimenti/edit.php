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

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="card-body">
            <div class="row">
                <div class="col-md-9">
                    {[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$title$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Tipo movimento'); ?>", "name": "tipo_movimento", "values": "list=\"carico\":\"Carico\",\"scarico\":\"Scarico\",\"spostamento\":\"Spostamento\"", "value": "$tipo_movimento$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "textarea", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$description$" ]}
                </div>
            </div>
		</div>
	</div>
</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
