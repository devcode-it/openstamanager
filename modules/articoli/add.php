<?php

include_once __DIR__.'/../../core.php';

unset($_SESSION['superselect']['id_categoria']);

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Inserisci il codice:'); ?>", "name": "codice", "required": 0, "value": "<?php echo htmlentities(filter('codice')) ?: ''; ?>", "help": "<?php echo tr('Se non specificato, il codice verrà calcolato automaticamente'); ?>", "validation": "codice" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Inserisci la descrizione:'); ?>", "name": "descrizione", "required": 1, "value": "<?php echo htmlentities(filter('descrizione')) ?: ''; ?>" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Inserisci la categoria:'); ?>", "name": "categoria", "required": 0, "ajax-source": "categorie", "icon-after": "add|<?php echo Modules::get('Categorie articoli')['id']; ?>" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Inserisci la sottocategoria:'); ?>", "name": "subcategoria", "id": "subcategoria_add", "ajax-source": "sottocategorie", "icon-after": "add|<?php echo Modules::get('Categorie articoli')['id']; ?>||hide" ]}
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
$(document).ready(function () {
    var sub = $('#add-form').find('#subcategoria_add');
    var original = sub.parent().find(".input-group-addon button").data("href");

    $('#add-form').find('#categoria').change( function(){
        session_set('superselect,id_categoria', $(this).val(), 0);

        sub.selectReset();

        if($(this).val()){
            sub.parent().find(".input-group-addon button").removeClass("hide");
            sub.parent().find(".input-group-addon button").data("href", original + "&id_original="+$(this).val());
        }
        else {
            sub.parent().find(".input-group-addon button").addClass("hide");
        }
    });
});
</script>
