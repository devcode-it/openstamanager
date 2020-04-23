<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Template email').'", "name": "id_template", "values": "query=SELECT id, name AS descrizione FROM em_templates", "required": 1 ]}
        </div>
        
        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Nome').'", "name": "name", "required": 1 ]}
        </div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';
