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

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="editriga">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
	<input type="hidden" name="idmastrino" value="<?php echo $record['idmastrino']; ?>">
	<input type="hidden" name="iddocumento" value="<?php echo $record['iddocumento']; ?>">


    <div class="row">
        <div class="col-md-5">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
		</div>
		<div class="col-md-7">
			{[ "type": "text", "label": "<?php echo tr('Causale'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
		</div>
	</div>

<?php

$conti3 = []; // contenitore conti di terzo livello
$idmastrino = $record['idmastrino'];

// Salvo l'elenco conti in un array (per non fare il ciclo ad ogni riga)
$query2 = 'SELECT * FROM co_pianodeiconti2';
$conti2 = $dbo->fetchArray($query2);
for ($x = 0; $x < sizeof($conti2); ++$x) {
    $query3 = 'SELECT * FROM co_pianodeiconti3 WHERE idpianodeiconti2='.prepare($conti2[$x]['id']);
    $rs3 = $dbo->fetchArray($query3);
    for ($y = 0; $y < sizeof($rs3); ++$y) {
        // Creo un array con le descrizioni dei conti di livello 3 che ha come indice l'id del livello2 e del livello3
        $conti3[$rs3[$y]['idpianodeiconti2']][$y]['id'] = $rs3[$y]['id'];
        $conti3[$rs3[$y]['idpianodeiconti2']][$y]['descrizione'] = $conti2[$x]['numero'].'.'.$rs3[$y]['numero'].' '.$rs3[$y]['descrizione'];
    }
}

/*
    Form di modifica riga movimento
*/
echo '
    <button class="btn btn-info btn-xs pull-right" type="button" onclick="addRiga(this)">
        <i class="fa fa-plus"></i> '.tr('Aggiungi riga').'
    </button>
    <br><br>
    <table class="table table-striped table-condensed table-hover table-bordered"
        <tr>
            <th width="60%">'.tr('Conto').'</th>
            <th width="20%">'.tr('Dare').'</th>
            <th width="20%">'.tr('Avere').'</th>
        </tr>';

// Lettura movimenti del mastrino selezionato
$rs = $dbo->fetchArray('SELECT * FROM co_movimenti_modelli WHERE idmastrino='.prepare($record['idmastrino']));
$counter = 0;

foreach ($rs as $r) {
    renderRiga($counter++, $r);
}

echo '
        </table>';
?>


<script type="text/javascript">
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

</form>

<?php
// Variabili utilizzabili
$variables = include Modules::filepath((new Module())->getByField('name', 'Fatture di vendita', Models\Locale::getPredefined()->id), 'variables.php');

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


<a class="btn btn-danger ask" data-backto="record-list" data-idmastrino="<?php echo $record['idmastrino']; ?>">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>


<script>

$('select[name=idconto]').each(function() {
    this.selectAdd([{
        'value': -1,
        'text': "Conto cliente fattura",
    }]);
});

</Script>
