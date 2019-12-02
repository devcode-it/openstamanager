<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="add">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data di ricezione').'", "name": "data", "required": 1, "value": "-now-" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Numero protocollo').'", "name": "numero_protocollo", "required": 1 ]}
		</div>
		
		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Numero progressivo').'", "name": "numero_progressivo", "required": 1 ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data di inizio').'", "name": "data_inizio", "required": 1 ]}
		</div>
		
        <div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data di fine').'", "name": "data_fine", "required": 1 ]}
		</div>
		
		<div class="col-md-4">
			{[ "type": "number", "label": "'.tr('Massimale').'", "name": "massimale", "required": 1, "icon-after": "'.currency().'" ]}
		</div>
	</div>
	
    <div class="row">
		<div class="col-md-6">
			{[ "type": "date", "label": "'.tr('Data protocollo').'", "name": "data_protocollo" ]}
		</div>
		
        <div class="col-md-6">
			{[ "type": "date", "label": "'.tr('Data di emissione').'", "name": "data_emissione" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';
