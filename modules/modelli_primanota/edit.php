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
    <table class="table table-striped table-condensed table-hover table-bordered"
        <tr>
            <th>'.tr('Conto').'</th>
        </tr>';

// Lettura movimenti del mastrino selezionato
$rs = $dbo->fetchArray('SELECT * FROM co_movimenti_modelli WHERE idmastrino='.prepare($record['idmastrino']));
for ($i = 0; $i < 10; ++$i) {
    $required = ($i <= 1) ? 1 : 0;

    // Conto
    echo '
			<tr>
				<td>
					{[ "type": "select", "name": "idconto['.$i.']", "value": "'.$rs[$i]['idconto'].'", "ajax-source": "conti-modelliprimanota", "required": "'.$required.'" ]}
				</td>
			</tr>';
}

echo '
        </table>';
?>


	<script type="text/javascript">
		$(document).ready( function() {
			$('select').on('change', function() {
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
	</script>

</form>

<?php
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
