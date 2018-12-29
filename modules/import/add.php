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
			{[ "type": "file", "label": "<?php echo tr('File'); ?>", "name": "blob", "required": 1 ]}
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
		 $( "#example" ).removeClass('hide');
	 }else{
		$( "#example" ).addClass('hide');
	 }
});

$( "#example" ).click(function(event) {
    var module =  $('#id_record').find(':selected').data('directory').toLowerCase();
    var dir = "<?php echo ROOTDIR; ?>/modules/"+module+"/import.php";
    var file = "<?php echo ROOTDIR; ?>/files/"+module+"/"+module+".csv";

    $.ajax({
        url: dir,
        type: 'post',
        data: {
            op: 'example', module: module
        },
        success: function(data){
            window.location = file;
            $('#main_loading').fadeOut();
            return false;
        }
	});
});
</script>
