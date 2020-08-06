<?php

include_once __DIR__.'/../../core.php';

$list = [];
foreach ($moduli_disponibili as $id => $value) {
    $modulo = Modules::get($id);

    $list[] = [
        'id' => $id,
        'text' => $modulo['title'],
    ];
}

?><form action="" method="post" id="add-form" enctype="multipart/form-data">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "file", "label": "<?php echo tr('File'); ?>", "name": "file", "required": 1, "accept": ".csv" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "module", "required": 1, "values": <?php echo json_encode($list); ?> ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button id="example" type="button" class="btn btn-info hidden">
                <i class="fa fa-file"></i> <?php echo tr('Scarica esempio CSV'); ?>
            </button>

			<button type="submit" class="btn btn-primary">
                <i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?>
            </button>
		</div>
	</div>
</form>

<script>
    $("#module").change(function () {
        if ($(this).val()) {
            $("#example").removeClass("hidden");
        } else {
            $("#example").addClass("hidden");
        }
    });

    $("#example").click(function() {
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "post",
            data: {
                op: "example",
                id_module: globals.id_module,
                module: $('#module').val(),
            },
            success: function(data) {
                if (data) {
                    window.location = data;
                }

                $('#main_loading').fadeOut();
            }
        });
    });
</script>
