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
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-5">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1 ]}
		</div>
		<div class="col-md-7">
			{[ "type": "text", "label": "<?php echo tr('Causale'); ?>", "name": "descrizione", "required": 1 ]}
		</div>
	</div>


	<?php

    // Salvo l'elenco conti in un array (per non fare il ciclo ad ogni riga)

    /*
        Form di aggiunta riga movimento
    */
    echo '
    <table class="table table-striped table-condensed table-hover table-bordered"
        <tr>
			<th width="60%">'.tr('Conto').'</th>
			<th width="20%">'.tr('Dare').'</th>
			<th width="20%">'.tr('Avere').'</th>
        </tr>';

    for ($i = 0; $i < 10; ++$i) {
        $required = ($i <= 1) ? 1 : 0;

        // Conto
        echo '
			<tr>
				<td>
					{[ "type": "select", "name": "idconto['.$i.']", "ajax-source": "conti", "required": "'.$required.'" ]}
				</td>

				<td class="text-right">
                    {[ "type": "number", "name": "dare['.$i.']", "id": "dare'.$id.'", "value": "'.($rs[$i]['totale'] > 0 ? $rs[$i]['totale'] : '').'"]}
                </td>
                
                <td class="text-right">
                    {[ "type": "number", "name": "avere['.$i.']", "id": "avere'.$id.'", "value": "'.($rs[$i]['totale'] < 0 ? abs($rs[$i]['totale']) : '').'"]}
                </td>
			</tr>';
    }

    echo '
        </table>';

    // Variabili utilizzabili
    $variables = include Modules::filepath(Modules::get('Fatture di vendita')['id'], 'variables.php');

    echo '
		<!-- Istruzioni per il contenuto -->
		<div class="box box-info">
			<div class="box-body">';

    if (!empty($variables)) {
        echo '
				<p>'.tr("Puoi utilizzare le seguenti sequenze di testo all'interno del campo causale, verranno sostituite in fase generazione prima nota dalla fattura.").':</p>
				<ul>';

        foreach ($variables as $variable => $value) {
            echo '
					<li><code>{'.$variable.'}</code></li>';
        }

        echo '
				</ul>';
    } else {
        echo '
				<p><i class="fa fa-warning"></i> '.tr('Non sono state definite variabili da utilizzare nel template').'.</p>';
    }

    echo '
			</div>
		</div>';
?>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>

<script>
	$(document).ready( function() {
		$('#modals select').on('change', function() {
			if($(this).parent().parent().find('input[disabled]').length != 1){
				if($(this).val()) {
					$(this).parent().parent().find('input').prop("disabled", false);
				}
				else{
					$(this).parent().parent().find('input').prop("disabled", true);
					$(this).parent().parent().find('input').val("");
				}
			}
		});
	});

	$("#modals input[id*=dare], #modals input[id*=avere]").each(function() {
		if (input(this).get() === 0) {
			$(this).prop("disabled", true);
		} else {
			$(this).prop("disabled", false);
		}
	});
		
	$(document).on("keyup change", "#modals input[id*=dare]", function() {
		let row = $(this).parent().parent();

		if (!$(this).prop("disabled")) {
			row.find("#modals input[id*=avere]").prop("disabled", input(this).get() !== 0);
		}
	});

	$(document).on("keyup change", "#modals input[id*=avere]", function() {
		let row = $(this).parent().parent();

		if (!$(this).prop("disabled")) {
			row.find("#modals input[id*=dare]").prop("disabled", input(this).get() !== 0);
		}
	});
</script>