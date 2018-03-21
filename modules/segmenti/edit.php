<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- DATI ARTICOLO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Segmento'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
			
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "name", "required": 1, "class": "", "value": "$name$", "extra": "" ]}
				</div>
				
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Categoria'); ?>", "name": "category", "required": 1, "class": "", "value": "$category$", "extra": "" ]}
				</div>
				
				
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Maschera'); ?>", "name": "pattern", "required": 1, "class": "alphanumeric-mask", "value": "$pattern$", "maxlength": 25, "placeholder":"####/YY", "extra": "" ]}
				</div>
				
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "id_module_", "required": 1, "class": "", "values": "list=\"14\": \"Fatture di vendita\",  \"15\": \"Fatture di acquisto\"", "value": "$id_module$", "extra": "<?php echo ($records[0]['n_sezionali']<2) ? 'readonly' : ''; ?>" ]}
				</div>
				
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "required": 0, "class": "", "value": "$note$", "extra": "" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">

					<div class="alert alert-info" style="margin:0;">
						<h3 style="margin:0;"><?php echo tr('Istruzioni per il campo _FIELD_', [
                            '_FIELD_' => tr('Maschera'),
                        ]); ?></h3>

						<p><font style='font-size:20px;'><b>####</b></font> <?php echo tr('Questi caratteri vengono sostituiti con il numero progressivo della fattura, vengono aggiunti zeri non significativi per raggiungere il numero desiderato di caratteri'); ?>.</p>

						<p><font style='font-size:20px;'><b>YYYY</b></font> <?php echo tr("Questi caratteri vengono sosituiti con l'anno corrente a 4 cifre, è possibile specificare l'anno a 2 cifre con _YY_", [
                            '_YY_' => 'YY',
                        ]); ?>.</p>

						<p><?php echo tr("E' possibile aggiungere altri caratteri fissi, come lettere, trattini, eccetera, prima e/o dopo e/o tra le maschere _####_ e _YYYY_", [
                            '_####_' => '####',
                            '_YYYY_' => 'YYYY',
                        ]); ?>.</p>
						</p>
					</div>

				</div>
			</div>

		</div>
	</div>

</form>



<?php
    $fatture = $dbo->fetchArray('SELECT COUNT(*) AS tot_fatture FROM co_documenti WHERE id_segment='.prepare($id_record));
    $tot_fatture = $fatture[0]['tot_fatture'];
    if ($tot_fatture > 0) {

        echo "<div class='alert alert-danger' style='margin:0px;'>";
    	
    	echo tr("Ci sono _TOT_ fatture collegate a questo segmento del modulo '_MODULO_'. Il comando elimina è stato disattivato, eliminare le fatture per attivare il comando 'Elimina segmento'.", [
        	'_TOT_' => $tot_fatture,
			'_MODULO_' => $records[0]['modulo'],       			
        ]);

    	echo "</div>";



    } else if ($records[0]['n_sezionali']<2) {

    	echo "<div class='alert alert-danger' style='margin:0px;'>";
    	
    	echo tr("Questo è l'unico segmento per il modulo '_MODULO_'. Il comando elimina è stato disattivato.", [
        	'_MODULO_' => $records[0]['modulo'],                  
        ]);

    	echo "</div>";

    }else{
 ?>
<form action="" method="post" role="form" id="form-delete">
	<input type="hidden" name="backto" value="record-list">
	<input type="hidden" name="op" value="delete">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
	<button type="button" class="btn-link" onclick="if( confirm('Eliminare questo segmento?') ){ $('#form-delete').submit(); }"><span class="text-danger"><i class="fa fa-trash-o"></i> Elimina segmento</span></button>
</form>
<?php
    }
?>

