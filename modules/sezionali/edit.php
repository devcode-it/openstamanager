<form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- DATI ARTICOLO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">Sezionale</h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Salva modifiche</button>
			</div>
			<div class="clearfix"></div>


			<div class="row">
				<div class="col-md-3">
					{[ "type": "text", "label": "Nome", "name": "nome", "required": 1, "class": "", "value": "$nome$", "extra": "" ]}
				</div>
				<div class="col-md-3">
					{[ "type": "text", "label": "Maschera", "name": "maschera", "required": 1, "class": "", "value": "$maschera$", "extra": "" ]}
				</div>
				<div class="col-md-3">
					{[ "type": "select", "label": "Documenti", "name": "dir", "required": 1, "class": "", "values": "list=\"entrata\": \"Documenti di vendita\",  \"uscita\": \"Documenti di acquisto\"", "value": "$dir$", "extra": "" ]}
				</div>
				<div class="col-md-3">
					{[ "type": "select", "label": "Magazzino", "name": "idautomezzo", "required": 0, "class": "", "values": "query=SELECT id, nome AS descrizione FROM dt_automezzi", "value": "$idautomezzo$", "extra": "" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "Note", "name": "note", "required": 0, "class": "", "value": "$note$", "extra": "" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">

					<div class="alert alert-info" style="margin:0;">
						<p align="justify">
						Istruzioni per il campo Maschera:<br/>

						<font style='font-size:20px;'><b>####</b></font> Questi caratteri vengono sostituiti con il numero progressivo della fattura, vengono aggiunti zeri non significativi per raggiungere il numero desiderato di caratteri;<br/><br/>

						<font style='font-size:20px;'><b>YYYY</b></font> Questi caratteri vengono sosituiti con l'anno corrente a 4 cifre, è possibile specificare l'anno a 2 cifre con YY;<br/><br/>

						&Egrave; possibile aggiungere altri caratteri fissi, come lettere, trattini, eccetera, prima e/o dopo e/o tra le maschere #### e YYYY.<br/>
						</p>
					</div>

				</div>
			</div>

		</div>
	</div>

</form>



<?php
    $fatture = $dbo->fetchArray('SELECT COUNT(*) AS tot_fatture FROM co_documenti WHERE id_sezionale='.prepare($id_record));
    $tot_fatture = $fatture[0]['tot_fatture'];
    if ($tot_fatture > 0) {
        echo "<div class='alert alert-danger' style='margin:0px;'>Ci sono $tot_fatture fatture collegate a questo sezionale. Il comando elimina è stato disattivato, eliminare le fatture per attivare il comando \"Elimina sezionale\".</div>\n";
    } else {
        ?>
<form action="" method="post" role="form" id="form-delete">
	<input type="hidden" name="backto" value="record-list">
	<input type="hidden" name="op" value="delete">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
	<button type="button" class="btn-link" onclick="if( confirm('Eliminare questo Sezionale?') ){ $('#form-delete').submit(); }"><span class="text-danger"><i class="fa fa-trash-o"></i> Elimina sezionale</span></button>
</form>
<?php
    }
?>

