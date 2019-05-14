<?php

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
            <th>'.tr('Conto').'</th>
        </tr>';

    for ($i = 0; $i < 10; ++$i) {
        $required = ($i <= 1) ? 1 : 0;

        // Conto
        echo '
			<tr>
				<td>
					{[ "type": "select", "name": "idconto['.$i.']", "ajax-source": "conti", "required": "'.$required.'" ]}
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

