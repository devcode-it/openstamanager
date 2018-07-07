<?php
//Se lo stato intervento è uno di quelli di default, non lo lascio modificare
if ($records[0]['doc_associati'] > 0) {
    $warning_text = "<div class='alert alert-warning'>Non puoi eliminare questo categoria documento! Ci sono ".$records[0]['doc_associati'].' documenti associati!</div>';
} else {
    $attr = '';
    $warning_text = '';
}

echo $warning_text;
?>

<form action="" method="post" id="add-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">


<?php
    //Se il tipo di anagrafica è uno di quelli di default, non lo lascio modificare
    if (!$records[0]['default']) {
        ?>

	<div class="clearfix"></div>
<?php
    }
?>

	<div class="row">

		<div class="col-md-12">
			{[ "type": "text", "label": "Descrizione", "name": "descrizione", "required": 1, "class": "", "value": "$descrizione$", "extra": "" ]}
		</div>

	</div>
</form>

<?php
    //Se il tipo di anagrafica è uno di quelli di default, non lo lascio modificare
    if ($records[0]['doc_associati'] == 0) {
        ?>
		<form action="" method="post" role="form" id="form-delete">
			<input type="hidden" name="backto" value="record-list">
			<input type="hidden" name="op" value="delete">
			<button type="button" class="btn-link" onclick="if( confirm('Eliminare questo tipo di documento?') ){ $('#form-delete').submit(); }">
			<span class="text-danger"><i class="fa fa-trash-o"></i> Elimina tipo di documento</span></button>
		</form>
<?php
    }
?>
