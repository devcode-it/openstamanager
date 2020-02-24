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
