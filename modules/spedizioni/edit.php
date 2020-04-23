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
				<div class="col-md-9">
					{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
				</div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Spedizione predefinita'); ?>", "name": "predefined", "value": "$predefined$", "help":"<?php echo tr('Impostare questo tipo di spedizione come predefinito per i ddt'); ?>." ]}
                </div>
			</div>
		</div>
	</div>

</form>

<?php
// Collegamenti diretti (numerici)
$documenti = $dbo->fetchNum('SELECT id FROM dt_ddt WHERE idspedizione='.prepare($id_record).'
UNION SELECT id FROM co_documenti WHERE idspedizione='.prepare($id_record));

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
