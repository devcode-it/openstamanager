<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "span", "label": "<?php echo tr('Codice'); ?>", "name": "idtipointervento", "value": "$idtipointervento$" ]}
		</div>

		<div class="col-md-8">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
		</div>
	</div>

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Costi unitari'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Costo orario'); ?>", "name": "costo_orario", "required": 1, "value": "$costo_orario$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Costo km'); ?>", "name": "costo_km", "required": 1, "value": "$costo_km$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Diritto chiamata'); ?>", "name": "costo_diritto_chiamata", "required": 1, "value": "$costo_diritto_chiamata$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>
			</div>
		</div>
	</div>


	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Costi unitari riconosciuti al tecnico'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Costo orario'); ?>", "name": "costo_orario_tecnico", "required": 1, "value": "$costo_orario_tecnico$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Costo km'); ?>", "name": "costo_km_tecnico", "required_tecnico": 1, "value": "$costo_km_tecnico$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Diritto chiamata'); ?>", "name": "costo_diritto_chiamata_tecnico", "required": 1, "value": "$costo_diritto_chiamata_tecnico$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>
			</div>
		</div>
	</div>
</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<?php

$interventi = $dbo->fetchArray('SELECT COUNT(*) AS tot_interventi FROM in_interventi WHERE idtipointervento='.prepare($id_record));

$tot_interventi = $interventi[0]['tot_interventi'];
if ($tot_interventi > 0) {
    echo '
    <div class="alert alert-danger">
        '.tr('Ci sono _NUM_ interventi collegati', [
            '_NUM_' => $tot_interventi,
        ]).'.
        '.tr('Eliminando questo tipo di attività, vengono rimossi anche gli interventi collegati!').'
    </div>';
}
