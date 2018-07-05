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
				<div class="col-md-12">
					{[ "type": "text", "label": "<?php echo tr('Valore'); ?>", "name": "valore", "required": 1,  "value": "$valore$" ]}
				</div>
			</div>
		</div>
	</div>

</form>

<?php
$righe = $dbo->fetchNum('SELECT id FROM co_righe_documenti WHERE um='.prepare($records[0]['valore']).'
			 UNION SELECT id FROM dt_righe_ddt WHERE um='.prepare($records[0]['valore']).'
			 UNION SELECT id FROM or_righe_ordini WHERE um='.prepare($records[0]['valore']).'
			 UNION SELECT id FROM co_righe_contratti WHERE um='.prepare($records[0]['valore']).'
			 UNION SELECT id FROM mg_articoli WHERE um='.prepare($records[0]['valore']).'
			 UNION SELECT id FROM co_righe_preventivi WHERE um='.prepare($records[0]['valore']));

if (!empty($righe)) {
    echo '
<div class="alert alert-danger">
    '.tr('Ci sono _NUM_ righe collegate', [
        '_NUM_' => count($righe),
    ]).'.
</div>';
}
?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
