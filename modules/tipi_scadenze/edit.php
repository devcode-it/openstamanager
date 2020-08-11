<?php
include_once __DIR__.'/../../core.php';

// Collegamenti con scadenzaio (numerici)
$scadenze = $dbo->fetchNum('SELECT id FROM  co_scadenziario WHERE tipo = '.prepare($record['nome']));

if ($record['can_delete'] and empty($scadenze)) {
    $attr = '';
} else {
    $attr = 'readonly';
    echo '<div class="alert alert-warning">'.tr('Alcune impostazioni non possono essere modificate per questo tipo di scadenza.').'</div>';
}
?>
<form action="" method="post" id="edit-form">
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
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$", "extra": "<?php echo $attr; ?>"  ]}
				</div>

				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
				</div>

			</div>
		</div>
	</div>

</form>

<?php

if ($record['can_delete']) {
    if (!empty($scadenze)) {
        echo '
	<div class="alert alert-danger">
		'.tr('Ci sono _NUM_ scadenze collegate', [
            '_NUM_' => count($scadenze),
        ]).'.
	</div>';
    } ?>

	<a class="btn btn-danger ask" data-backto="record-list">
		<i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
	</a>

<?php
}
?>
