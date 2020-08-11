<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="edit-form">

	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": "1", "value": "$nome$" ]}
                </div>
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Filiale'); ?>", "name": "filiale", "value": "$filiale$" ]}
                </div>
				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Conto predefinito'); ?>", "name": "id_pianodeiconti3", "value": "$id_pianodeiconti3$", "values": "query=SELECT id, descrizione  FROM co_pianodeiconti3 WHERE idpianodeiconti2 = 1" ]}
                </div>
			</div>
			<div class="row">
				<div class="col-md-8">
					{[ "type": "text", "label": "<?php echo tr('IBAN'); ?>", "name": "iban", "required": "1", "class": "alphanumeric-mask", "maxlength": 32, "value": "$iban$" ]}
                </div>
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('BIC'); ?>", "name": "bic", "class": "alphanumeric-mask", "maxlength": 11, "value": "$bic$" ]}
                </div>
			</div>
			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
				</div>
			</div>
		</div>
	</div>

</form>

<?php
// Collegamenti diretti (numerici)
$documenti = $dbo->fetchNum('SELECT idanagrafica FROM an_anagrafiche WHERE idbanca_vendite='.prepare($id_record).'
UNION SELECT idanagrafica FROM an_anagrafiche WHERE idbanca_acquisti='.prepare($id_record).'
UNION SELECT idanagrafica FROM co_documenti WHERE idbanca='.prepare($id_record));

if (!empty($documenti)) {
    echo '
<div class="alert alert-danger">
    '.tr('Ci sono _NUM_ documenti collegati', [
        '_NUM_' => count($documenti),
    ]).'.
</div>';
}
?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
