<?php

include_once __DIR__.'/../../core.php';

$list = [];
foreach ($imports as $key => $value) {
    $list[] = [
        'id' => $key,
        'text' => $value['title'],
    ];
}

// Utilizzo le funzionalità di filelist_and_upload
?><form action="" method="post" id="add-form" enctype="multipart/form-data">
	<input type="hidden" name="op" value="link_file">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "file", "label": "<?php echo tr('File'); ?>", "name": "blob", "required": 1 ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "id_record", "values": <?php echo json_encode($list); ?> ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>
