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

function renderRiga($id, $riga)
{
    // Conto
    echo '
    <tr>
        <td>
            {[ "type": "select", "name": "idconto['.$id.']", "id": "conto'.$id.'", "value": "'.($riga['idconto'] ?: '').'", "ajax-source": "conti-modelliprimanota" ]}
        </td>';

    // Dare
    echo '
        <td>
            {[ "type": "number", "name": "dare['.$id.']", "id": "dare'.$id.'", "value": "'.($riga['totale'] > 0 ? abs($riga['totale']) : 0).'" ]}
        </td>';

    // Avere
    echo '
        <td>
            {[ "type": "number", "name": "avere['.$id.']", "id": "avere'.$id.'", "value": "'.($riga['totale'] < 0 ? abs($riga['totale']) : 0).'" ]}
        </td>
    </tr>';
}

// Nuova riga
echo '
<table class="hide">
    <tbody id="template">';

renderRiga('-id-', null);

echo '
    </tbody>
</table>';

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
	<button class="btn btn-info btn-xs pull-right" type="button" id="addriga" onclick="addRiga(this)">
        <i class="fa fa-plus"></i> '.tr('Aggiungi riga').'
    </button>
    <br><br>
    <table class="table table-striped table-condensed table-hover table-bordered"
        <tr>
			<th width="60%">'.tr('Conto').'</th>
			<th width="20%">'.tr('Dare').'</th>
			<th width="20%">'.tr('Avere').'</th>
        </tr>';

$counter = 0;

foreach ($rs as $r) {
    renderRiga($counter++, $r);
}

echo '
        </table>';

// Variabili utilizzabili
$variables = include Modules::filepath((new Module())->getByField('title', 'Fatture di vendita', Models\Locale::getPredefined()->id), 'variables.php');

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
	$(document).ready(function() {
		// Fix per l\'inizializzazione degli input
        $("input[id*=dare], input[id*=avere]").each(function() {
            if (input(this).get() === 0) {
                $(this).prop("disabled", true);
            } else {
                $(this).prop("disabled", false);
            }
        });

        // Trigger dell\'evento keyup() per la prima volta, per eseguire i dovuti controlli nel caso siano predisposte delle righe in prima nota
        $("input[id*=dare][value!=\'\'], input[id*=avere][value!=\'\']").keyup();

        $("select[id*=idconto]").click(function() {
            $("input[id*=dare][value!=\'\'], input[id*=avere][value!=\'\']").keyup();
        });

		// Creazione delle eventuali righe aggiuntive
		let table = $("#modals table").first();
		let button = table.parent().find("button").first();
		let row_needed = $("#modals select").length;
		let row_count = table.find("tr").length - 2;
		while (row_count < row_needed) {
			button.click();
			row_count++;
		}
		
    });

    $(document).on("change", "select", function() {
        let row = $(this).parent().parent().parent();

        if (row.find("input[disabled]").length > 1) {
            row.find("input").prop("disabled", !$(this).val());
        }

        // Trigger dell\'evento keyup() per la prima volta, per eseguire i dovuti controlli nel caso siano predisposte delle righe in prima nota
        $("input[id*=dare][value!=\'\'], input[id*=avere][value!=\'\']").keyup();
    });

    $(document).on("keyup change", "input[id*=dare]", function() {
        let row = $(this).parent().parent().parent();

        if (!$(this).prop("disabled")) {
            row.find("input[id*=avere]").prop("disabled", $(this).val().toEnglish());
        }
    });

    $(document).on("keyup change", "input[id*=avere]", function() {
        let row = $(this).parent().parent().parent();

        if (!$(this).prop("disabled")) {
            row.find("input[id*=dare]").prop("disabled", $(this).val().toEnglish());
        }
    });

    var n = <?php echo $counter; ?>;
    function addRiga(btn) {
        var raggruppamento = $(btn).parent();
        cleanup_inputs();

        var tabella = raggruppamento.find("tbody");
        var content = $("#template").html();
        var text = replaceAll(content, "-id-", "" + n);
        tabella.append(text);

        restart_inputs();
        n++;
    }

    $(document).ready(init);
</script>