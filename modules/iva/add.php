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

$esigibilita = [
    [
        'id' => 'I',
        'text' => tr('IVA ad esigibilità immediata'),
    ],
    [
        'id' => 'D',
        'text' => tr('IVA ad esigibilità differita'),
    ],
    [
        'id' => 'S',
        'text' => tr('Scissione dei pagamenti'),
    ],
];

use Modules\Iva\Aliquota;

$codice = Aliquota::max('codice') + 1;

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "required": 1, "decimals": 0, "min-value": "0", "max-value": "999", "maxlength": 3, "value": "<?php echo $codice; ?>", "validation": "codice" ]}
		</div>

		<div class="col-md-8">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1 ]}
		</div>
	</div>

	<div class="row">
        <div class="col-md-4">
            {[ "type": "checkbox", "label": "<?php echo tr('Esente'); ?>", "name": "esente", "id": "esente-add", "value": "$esente$" ]}
        </div>

		<div class="col-md-4">
			{[ "type": "number", "label": "<?php echo tr('Percentuale'); ?>", "name": "percentuale", "id": "percentuale-add", "icon-after": "<i class=\"fa fa-percent\"></i>", "max-value": "100" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Codice Natura (Fatturazione Elettronica)'); ?>", "name": "codice_natura_fe", "values": "query=SELECT `codice` as id, CONCAT(`codice`, ' - ', `name`) AS descrizione FROM `fe_natura` LEFT JOIN `fe_natura_lang` ON (`fe_natura`.`codice` = `fe_natura_lang`.`id_record` AND `fe_natura_lang`.`id_lang` = <?php echo prepare(\Models\Locale::getDefault()->id); ?>)", "extra": "disabled" ]}
		</div>

	</div>
	<div class="row">
		<div class="col-md-4">
			{[ "type": "number", "label": "<?php echo tr('Indetraibile'); ?>", "name": "indetraibile", "icon-after": "<i class=\"fa fa-percent\"></i>", "max-value": "100" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Esigibilità (Fatturazione Elettronica)'); ?>", "name": "esigibilita", "values": <?php echo json_encode($esigibilita); ?>, "required": 1 ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>

<script>
$(document).ready(function() {
    $('#modals > div #esente-add').change(function() {
        var checkbox = $(this).parent().find('[type=hidden]');

        if (checkbox.val() == 1) {
            $("#modals > div #percentuale-add").prop("disabled", true);
            $("#modals > div #codice_natura_fe").prop("required", true);
            $("#modals > div #codice_natura_fe").prop("disabled", false);
        } else {
            $("#modals > div #percentuale-add").prop("disabled", false);
            $("#modals > div #codice_natura_fe").prop("required", false);
            $("#modals > div #codice_natura_fe").val("").change();
            $("#modals > div #codice_natura_fe").prop("disabled", true);
        }
    });
});
</script>
