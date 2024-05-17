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

// $block_edit = $record['is_predefined'];

?>

<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
				</div>

                <div class="col-md-6">
					{[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "required": 1, "value": "$data$"  ]}
				</div>
			</div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "select", "label": "<?php echo tr('Nazione'); ?>", "name": "id_nazione",  "required": 1, "value": "$id_nazione$", "ajax-source": "nazioni" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "select", "label": "<?php echo tr('Regione'); ?>", "name": "id_regione", "value": "$id_regione$", "ajax-source": "regioni", "select-options": <?php echo json_encode(['id_nazione' => $record['id_nazione']]); ?> ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "checkbox", "label": "<?php echo tr('Ricorrente'); ?>", "name": "is_recurring", "value": "$is_recurring$" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "checkbox", "label": "<?php echo tr('Festività'); ?>", "name": "is_bank_holiday", "value": "$is_bank_holiday$" ]}
                </div>
            </div>

		</div>
	</div>
</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<script>
    var nazione = input("id_nazione");
    var regione = input("id_regione");

    // Gestione della modifica della nazione
	nazione.change(function() {

        updateSelectOption("id_nazione", $(this).val());
        session_set("superselect,id_nazione", $(this).val(), 0);

        let value = !$(this).val();
        let placeholder = value ? "<?php echo tr('Seleziona prima una nazione'); ?>" : "<?php echo tr("Seleziona un'opzione"); ?>";

        regione.getElement()
            .selectReset(placeholder);
    });
</script>