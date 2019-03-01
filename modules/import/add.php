<?php

include_once __DIR__.'/../../core.php';

$list = [];
foreach ($imports as $key => $value) {
    $list[] = [
        'id' => $key,
        'text' => $value['title'],
        'directory' => $value['directory'],
    ];
}

// Utilizzo le funzionalità di filelist_and_upload
?><form action="" method="post" id="add-form" enctype="multipart/form-data">
	<input type="hidden" name="op" value="link_file">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "file", "label": "<?php echo tr('File'); ?>", "name": "blob", "required": 1, "extra": "accept=\".csv\"" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "id_record", "required": 1, "values": <?php echo json_encode($list); ?> ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button id="example" type="button" class="btn btn-info hide" ><i class="fa fa-file"></i> <?php echo tr('Scarica esempio CSV'); ?></button>
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>

<script>
$('#id_record').change( function(){
    if ($(this).val()>0){
        $("#example").removeClass('hide');
    } else {
		$("#example").addClass('hide');
    }
});

$( "#example" ).click(function(event) {
    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: 'post',
        data: {
            op: 'example',
            id_module: globals.id_module,
            id_record: $('#id_record').val(),
        },
        success: function(data){
            window.location = data;
            $('#main_loading').fadeOut();
            return false;
        }
	});
});
</script>
