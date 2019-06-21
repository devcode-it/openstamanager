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

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
				</div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Causale predefinita'); ?>", "name": "predefined", "value": "$predefined$", "help":"<?php echo tr('Scadenza di sistema, impossibile modificare'); ?>." ]}
                </div>
			</div>
		</div>
	</div>

</form>

<?php

// Collegamenti diretti (numerici)
$scadenze = $dbo->fetchNum('SELECT id FROM  co_scadenziario WHERE tipo = '.prepare($record['nome']));

if (!empty($scadenze)) {
    echo '
<div class="alert alert-danger">
    '.tr('Ci sono _NUM_ scadenze collegate', [
        '_NUM_' => count($scadenze),
    ]).'.
</div>';
}
?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
