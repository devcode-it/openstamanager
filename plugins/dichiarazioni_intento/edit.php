<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" role="form" id="form_sedi">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
    <input type="hidden" name="id_record" value="'.$record['id'].'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data di ricezione').'", "name": "data", "required": 1, "value": "'.$record['data'].'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Numero protocollo').'", "name": "numero_protocollo", "required": 1, "value": "'.$record['numero_protocollo'].'" ]}
		</div>
		
		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Numero progressivo').'", "name": "numero_progressivo", "required": 1, "value": "'.$record['numero_progressivo'].'" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data di inizio').'", "name": "data_inizio", "required": 1, "value": "'.$record['data_inizio'].'" ]}
		</div>
		
        <div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data di fine').'", "name": "data_fine", "required": 1, "value": "'.$record['data_fine'].'" ]}
		</div>
		
		<div class="col-md-4">
			{[ "type": "number", "label": "'.tr('Massimale').'", "name": "massimale", "required": 1, "icon-after": "'.currency().'", "value": "'.$record['massimale'].'" ]}
		</div>
	</div>
	
    <div class="row">
		<div class="col-md-6">
			{[ "type": "date", "label": "'.tr('Data protocollo').'", "name": "data_protocollo", "value": "'.$record['data_protocollo'].'" ]}
		</div>
		
        <div class="col-md-6">
			{[ "type": "date", "label": "'.tr('Data di emissione').'", "name": "data_emissione", "value": "'.$record['data_emissione'].'" ]}
		</div>
	</div>
	
	<p><b>'.tr('Totale utilizzato').':</b> '.moneyFormat($record['totale']).'</p>
	
	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
            <a class="btn btn-danger ask" data-backto="record-edit" data-op="delete" data-id_record="'.$record['id'].'" data-id_plugin="'.$id_plugin.'" data-id_parent="'.$id_parent.'">
                <i class="fa fa-trash"></i> '.tr('Elimina').'
            </a>

			<button type="submit" class="btn btn-primary pull-right">
			    <i class="fa fa-plus"></i> '.tr('Modifica').'
			</button>
		</div>
	</div>
</form>';
