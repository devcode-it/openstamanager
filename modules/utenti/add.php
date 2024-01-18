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
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-list">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Nome gruppo'); ?>", "name": "nome", "required": 1, "validation": "gruppo", "help": "<?php echo tr('Compilando questo campo verrà creato un nuovo gruppo di utenti.'); ?>" ]}
		</div>
		<div class="col-md-3">
			{[ "type": "select", "label": "<?php echo tr('Tema'); ?>", "name": "theme", "values": "list=\"\": \"<?php echo tr('Predefinito'); ?>\",\"black\": \"<?php echo tr('Nero'); ?>\",\"red\": \"<?php echo tr('Rosso'); ?>\",\"blue\": \"<?php echo tr('Blu'); ?>\",\"green\": \"<?php echo tr('Verde'); ?>\",\"yellow\": \"<?php echo tr('Giallo'); ?>\",\"purple\": \"<?php echo tr('Viola'); ?>\"", "value": "$theme$" ]}
		</div>
		<div class="col-md-3">
			 {["type":"select", "label":"<?php echo tr('Modulo iniziale'); ?>", "name":"id_module_start", "ajax-source":"moduli_gruppo", "placeholder":"<?php tr('Modulo iniziale'); ?>" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="button" onclick="submitCheck()" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>



<script>
function submitCheck() {
	var nome = parseInt($("#nome").attr("valid"));

	if(nome) {
		$("#add-form").submit();
	}else{
		$("input[name=nome]").focus();
		swal("<?php echo tr('Impossibile procedere'); ?>", "<?php echo tr('Nome gruppo già utilizzato'); ?>.", "error");
		
	}
}
</script>