<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1 ]}
		</div>

        <div class="col-md-4">
            {[ "type": "text", "label": "<?php echo tr('Sconto/rincaro combinato'); ?>", "name": "prc_combinato", "value": "$prc_combinato$", "icon-after": "%", "class": "math-mask text-right", "help": "<?php echo tr('Esempio: 50+10-20 viene convertito in 50% di sconto con 10% aggiuntivo sul totale scontato e 20% di maggiorazione sul totale finale (54% di sconto finale)').'. '.tr('Sono ammessi i segni + e -').'.'; ?>" ]}
        </div>

		<div class="col-md-4">
			{[ "type": "number", "label": "<?php echo tr('Sconto/rincaro'); ?>", "name": "prc_guadagno", "required": 1, "value": "0", "icon-after": "%", "help": "<?php echo tr('Il valore positivo indica uno sconto: per applicare una maggiorazione inserire un valore negativo'); ?>" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>

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

    $(document).ready(function () {
        $("#prc_combinato").change();
        $("#prc_guadagno").change();
    })
</script>
