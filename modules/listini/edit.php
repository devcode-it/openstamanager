<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI ARTICOLO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Listino'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
				</div>

                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('Rincaro/sconto combinato'); ?>", "name": "prc_combinato", "value": "$prc_combinato$", "icon-after": "%", "class": "math-mask text-right", "help": "<?php echo tr('Esempio: 50+10-20 viene convertito in 50% di sconto con 10% aggiuntivo sul totale scontato e 20% di maggiorazione sul totale finale (54% di sconto finale)').'. '.tr('Sono ammessi i segni +/-').'.'; ?>", "disabled": "<?php echo intval(empty($record['prc_combinato']) && !empty($record['prc_guadagno'])); ?>" ]}
                </div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Rincaro/sconto'); ?>", "name": "prc_guadagno", "required": 1, "value": "$prc_guadagno$", "icon-after": "%", "help": "<?php echo tr('Il valore positivo indica uno sconto').'. '.tr('Per applicare una percentuale di rincaro inserire un valore negativo').'.'; ?>", "disabled": "<?php echo intval(!empty($record['prc_combinato'])); ?>" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
				</div>
			</div>

		</div>
	</div>

</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<script>
    $("#prc_guadagno").change(function () {
        if ($(this).val() && $(this).val() != (0).toLocale()) {
            $("#prc_combinato").attr("disabled", true).addClass("disabled");
        } else {
            $("#prc_combinato").attr("disabled", false).removeClass("disabled");
        }
    });

    $("#prc_combinato").change(function () {
        if ($(this).val()) {
            $("#prc_guadagno").attr("disabled", true).addClass("disabled").attr("required", false);
        } else {
            $("#prc_guadagno").attr("disabled", false).removeClass("disabled").attr("required", true);
        }
    });
</script>
