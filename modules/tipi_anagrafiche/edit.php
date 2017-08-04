<?php

include_once __DIR__.'/../../core.php';

// Se lo stato intervento è uno di quelli di default, non lo lascio modificare
if ($records[0]['default']) {
    $attr = "readonly='true'";
    $warning_text = '<div class="alert alert-warning">'._('Non puoi modificare questo tipo di anagrafica!').'</div>';
} else {
    $attr = '';
    $warning_text = '';
}

echo $warning_text;
?>

<form action="" method="post">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record ?>">

<?php
// Se il tipo di anagrafica è uno di quelli di default, non lo lascio modificare
if (!$records[0]['default']) {
    ?>
	<div class="pull-right">
		<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo _('Salva modifiche'); ?></button>
	</div>
	<div class="clearfix"></div>
<?php

}
?>

	<div class="row">

		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo _('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$", "extra": "<?php echo $attr ?>" ]}
		</div>

	</div>
</form>

<?php
// Se il tipo di anagrafica è uno di quelli di default, non lo lascio modificare
if (!$records[0]['default']) {
    ?>
        <a class="btn btn-danger ask" data-backto="record-list">
            <i class="fa fa-trash"></i> <?php echo _('Elimina') ?>
        </a>

<?php

}
