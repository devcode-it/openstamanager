<?php

include_once __DIR__.'/../../core.php';

// Se lo stato intervento è uno di quelli di default, non lo lascio modificare
if ($record['default']) {
    $attr = "readonly='true'";
    $warning_text = '<div class="alert alert-warning">'.tr('Non puoi modificare questo tipo di anagrafica!').'</div>';
} else {
    $attr = '';
    $warning_text = '';
}

// Se il tipo di anagrafica è uno di quelli di default, non lo lascio modificare
if (!empty($record['default'])) {
    // Disabilito il pulsante di salvataggio
    echo '
    <script>
    $(document).ready(function() {
        $("#save").prop("disabled", true).addClass("disabled");
    });
    </script>

    '.$warning_text;
}

?>

<form action="" method="post">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<div class="row">

		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$", "extra": "<?php echo $attr; ?>" ]}
		</div>

	</div>
</form>

<?php

// Se il tipo di anagrafica è uno di quelli di default, non lo lascio modificare
if (empty($record['default'])) {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
}
