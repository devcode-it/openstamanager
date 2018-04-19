<form id="upload_form" action="editor.php?id_module=$id_module$" method="post" enctype="multipart/form-data" onsubmit="if( $('input[name=nome_allegato]').val()=='' || $('input[name=nome_allegato]').val()=='Inserisci un nome...' ){ alert('Devi inserire un nome per il file!'); return false; }  if( $('#blob').val()=='' ){ alert('Devi selezionare un file con il tasto Sfoglia...'); return false; }">
	
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class='row'>
		<div class="col-md-6">
			{[ "type": "text", "label": "Nome", "name": "nome", "required": 1, "class": "", "value": "", "extra": "" ]}
		</div>
				
		<div class="col-md-3">
			{[ "type": "select", "label": "Categoria", "name": "idcategoria", "required": 1, "class": "", "values": "query=SELECT id, descrizione FROM zz_documenti_categorie WHERE deleted = 0", "value": "", "extra": "" ]}
		</div>
		
		<div class="col-md-3">
			{[ "type": "text", "label": "Data", "name": "data", "required": 1, "class": "datepicker text-center", "value": "", "extra": "" ]}
		</div>
	</div>
	
	
	<div class='row'>
		<div class='col-md-6'>
		
			{[ "type": "text", "label": "Nome allegato", "name": "nome_allegato", "required": 1, "class": "", "value": "", "extra": " placeholder=\"Inserisci un nome...\"  " ]}
		</div>
		<div class='col-md-3'>
			<input type="file" class="inputtext" id="blob" name="blob"><br><br>
		</div>
	</div>
	
	<div class='row'>
		<div class='col-md-12'>
			<button type='submit' class='btn btn-primary pull-right' id="upload_button"><i class='fa fa-upload'></i> Carica</button>
		</div>
	</div>
	
</form>