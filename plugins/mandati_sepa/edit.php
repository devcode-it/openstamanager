<?php

include_once __DIR__.'/init.php';

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="mandato">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('ID Mandato').'", "name": "id_mandato", "required": 1, "value": "'.$mandato['id_mandato'].'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data firma mandato').'", "name": "data_firma_mandato", "required": 1, "value": "'.($mandato['data_firma_mandato'] ?: '2010-04-12').'", "help": "'.tr('Data di firma del mandato da parte della banca, ovvero di attivazione del servizio SEPA').'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "checkbox", "label": "'.tr('Singola disposizione non ripetuta').'", "name": "singola_disposizione", "value": "'.$mandato['singola_disposizione'].'" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
            <a class="btn btn-danger ask" data-backto="record-edit" data-op="delete" data-id_record="'.$mandato['id'].'" data-id_plugin="'.$id_plugin.'" data-id_parent="'.$id_parent.'">
                <i class="fa fa-trash"></i> '.tr('Elimina').'
            </a>

			<button type="submit" class="btn btn-primary pull-right">
			    <i class="fa fa-save"></i> '.tr('Salva').'
			</button>
		</div>
	</div>
</form>';
