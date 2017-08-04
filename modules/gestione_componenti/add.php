<?php

include_once __DIR__.'/../../core.php';

?><form action="editor.php?id_module=$id_module$" method="post">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-list">

	<div class="row">
		<div class="col-md-12">
			{[ "type": "text", "label": "<?php echo _('Nome file'); ?>", "name": "nomefile", "required": 1, "value": "" ]}
		</div>


		<div class="col-md-12">
			<a href="#" class="pull-right" id="default">[Default]</a>
			{[ "type": "textarea", "label": "<?php echo _('Contenuto'); ?>", "name": "contenuto", "required": 1, "class": "autosize", "value": "", "extra": "rows='10'" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo _('Aggiungi'); ?></button>
		</div>
	</div>
</form>

<script type="text/javascript">
	$(document).ready( function(){
		$('#default').click( function(){
			if (confirm ('Generare un componente di default?')){

				var ini = '[Nome]\ntipo = span\nvalore = "Componente di esempio"\n\n[Marca]\ntipo = input\nvalore =\n\n[Tipo]\ntipo = select\nvalore =\nopzioni = "Tipo 1", "Tipo 2"\n\n[Data di installazione]\ntipo = date\nvalore =\n\n[Note]\ntipo = textarea\nvalore =\n';

				$( "#contenuto" ).val(ini);
			}
		});
	});
</script>
