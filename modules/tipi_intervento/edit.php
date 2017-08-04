<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record ?>">

	<!-- DATI CLIENTE -->
	<div class="pull-right">
		<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo _('Salva modifiche'); ?></button>
	</div>
	<div class="clearfix"></div>

	<div class="row">
		<div class="col-md-4">
			{[ "type": "span", "label": "<?php echo _('Codice'); ?>", "name": "idtipointervento", "value": "$idtipointervento$" ]}
		</div>

		<div class="col-md-8">
			{[ "type": "text", "label": "<?php echo _('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
		</div>
	</div>

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _('Costi unitari'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-3">
					{[ "type": "number", "label": "<?php echo _('Costo orario'); ?>", "name": "costo_orario", "required": 1, "value": "$costo_orario$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "number", "label": "<?php echo _('Costo km'); ?>", "name": "costo_km", "required": 1, "value": "$costo_km$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "number", "label": "<?php echo _('Diritto chiamata'); ?>", "name": "costo_diritto_chiamata", "required": 1, "value": "$costo_diritto_chiamata$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>
			</div>
		</div>
	</div>


	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _('Costi unitari riconosciuti al tecnico'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-3">
					{[ "type": "number", "label": "<?php echo _('Costo orario'); ?>", "name": "costo_orario_tecnico", "required": 1, "value": "$costo_orario_tecnico$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "number", "label": "<?php echo _('Costo km'); ?>", "name": "costo_km_tecnico", "required_tecnico": 1, "value": "$costo_km_tecnico$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "number", "label": "<?php echo _('Diritto chiamata'); ?>", "name": "costo_diritto_chiamata_tecnico", "required": 1, "value": "$costo_diritto_chiamata_tecnico$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>
			</div>
		</div>
	</div>
</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo _('Elimina'); ?>
</a>

<?php

$interventi = $dbo->fetchArray('SELECT COUNT(*) AS tot_interventi FROM in_interventi WHERE idtipointervento='.prepare($id_record));

$tot_interventi = $interventi[0]['tot_interventi'];
if ($tot_interventi > 0) {
    echo '
    <div class="alert alert-danger" style="margin:0px;">
        '.str_replace('_NUM_', $tot_interventi, _('Ci sono _NUM_ interventi collegati')).'.
        '._('Eliminando questo tipo di attività, vengono rimossi anche gli interventi collegati!').'.
    </div>';
}
