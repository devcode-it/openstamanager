<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- DATI SEGMENTO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Informazioni della stampa'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">

				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Titolo'); ?>", "name": "title", "required": 1, "value": "$title$" ]}
				</div>

				<div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Nome del file'); ?>", "name": "filename", "required": 1, "value": "$filename$" ]}
				</div>
			</div>

			<div class="row">

				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Opzioni'); ?>", "name": "options", "value": "$options$", "help": "<?php echo tr('Impostazioni personalizzabili della stampa, in formato JSON'); ?>" ]}
				</div>
            </div>
        </div>
    </div>
</form>

<?php
// Variabili utilizzabili
$variables = include Modules::filepath($record['id_module'], 'variables.php');

echo '
<!-- Istruzioni per il contenuto -->
<div class="box box-info">
    <div class="box-header">
        <h3 class="box-title">'.tr('Variabili').'</h3>
    </div>

    <div class="box-body">';

if (!empty($variables)) {
    echo '
        <p>'.tr('Puoi utilizzare le seguenti variabili per generare il nome del file').':</p>
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
</div>

<hr>';

?>