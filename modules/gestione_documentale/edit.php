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

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">

	<!-- SCHEDA FILE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"> Scheda file</h3>
		</div>

		<div class="panel-body">

			<div class="row">

				<div class="col-md-6">
					{[ "type": "text", "label": "Nome", "name": "nome", "required": 1, "value": "$nome$", "extra": "" ]}
				</div>



				<div class="col-md-3">
					{[ "type": "select", "label": "Categoria", "name": "idcategoria", "required": 1, "ajax-source": "categorie_documenti", "value": "$idcategoria$", "extra": "" ]}
				</div>


				<div class="col-md-3">
					{[ "type": "date", "label": "Data", "name": "data", "required": 1, "class": "datepicker text-center", "value": "$data$", "extra": "" ]}
				</div>

			</div>
		</div>
	</div>

</form>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
