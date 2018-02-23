<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">

		<div class="col-md-6">
			{[ "type": "text", "label": "Inserisci un nome per questo sezionale:", "name": "nome", "required": 1, "class": "", "value": "", "extra": "" ]}
		</div>

		<div class="col-md-6">
				{[ "type": "text", "label": "Maschera", "name": "maschera", "required": 1, "class": "", "value": "####YYYY", "extra": "" ]}
		</div>


	</div>


	<div class="row">


		<div class="col-md-6">
			{[ "type": "select", "label": "Documenti", "name": "dir", "required": 1, "class": "", "values": "list=\"entrata\": \"Documenti di vendita\",  \"uscita\": \"Documenti di acquisto\"", "value": "", "extra": "" ]}
		</div>
		<div class="col-md-6">
			{[ "type": "select", "label": "Magazzino", "name": "idautomezzo", "required": 0, "class": "", "values": "query=SELECT id, nome AS descrizione FROM dt_automezzi", "value": "", "extra": "" ]}
		</div>

	</div>

	<div class="row">

		<div class="col-md-12">
			{[ "type": "textarea", "label": "Note", "name": "note", "required": 0, "class": "", "value": "", "extra": "" ]}
		</div>

	</div>


	<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> Inserisci</button>
	<div class="clearfix"></div>
</form>
<!--script>
	$(document).ready( function(){
		start_jquerychosen();
	});
</script-->
