<?php

include_once __DIR__.'/../../core.php';

unset($_SESSION['superselect']['id_categoria']);

?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "required": 0, "value": "<?php echo htmlentities(filter('codice')) ?: ''; ?>", "help": "<?php echo tr('Se non specificato, il codice verrà calcolato automaticamente'); ?>", "validation": "codice" ]}
		</div>

        <div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Barcode'); ?>", "name": "barcode", "required": 0, "value": "<?php echo htmlentities(filter('barcode')) ?: ''; ?>", "validation": "barcode" ]}
		</div>
    </div>

    <div class="row">
		<div class="col-md-12">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "<?php echo htmlentities(filter('descrizione')) ?: ''; ?>" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Categoria'); ?>", "name": "categoria", "required": 0, "ajax-source": "categorie", "icon-after": "add|<?php echo Modules::get('Categorie articoli')['id']; ?>" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Sottocategoria'); ?>", "name": "subcategoria", "id": "subcategoria_add", "ajax-source": "sottocategorie", "icon-after": "add|<?php echo Modules::get('Categorie articoli')['id']; ?>||hide" ]}
		</div>
	</div>

    <div class="box box-info collapsed-box">
        <div class="box-header with-border">
            <h3 class="box-title"><?php echo tr('Informazioni aggiuntive'); ?></h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "number", "label": "<?php echo tr('Prezzo di acquisto'); ?>", "name": "prezzo_acquisto", "icon-after": "<?php echo currency(); ?>" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "number", "label": "<?php echo tr('Soglia minima quantità'); ?>", "name": "threshold_qta", "decimals": "qta", "min-value": "undefined" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?php
                    if (!setting('Utilizza prezzi di vendita comprensivi di IVA')) {
                        echo '
                    <button type="button" class="btn btn-info btn-xs pull-right tip pull-right" title="'.tr('Scorpora iva dal prezzo di vendita.').'" id="scorpora_iva_add"><i class="fa fa-calculator" aria-hidden="true"></i></button>';
                    }
                    ?>

                    {[ "type": "number", "label": "<?php echo tr('Prezzo di vendita'); ?>", "name": "prezzo_vendita", "icon-after": "<?php echo currency(); ?>", "help": "<?php echo setting('Utilizza prezzi di vendita comprensivi di IVA') ? tr('Importo IVA inclusa') : ''; ?>" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "select", "label": "<?php echo tr('Iva di vendita'); ?>", "name": "idiva_vendita", "ajax-source": "iva", "valore_predefinito": "Iva predefinita", "help": "<?php echo tr('Se non specificata, verrà utilizzata l\'iva di default delle impostazioni'); ?>" ]}
                </div>
            </div>
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

    $('#add-form').find('#categoria').change(function() {
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

    $("#scorpora_iva_add").click( function() {
        scorpora_iva_add();
    });
});

function scorpora_iva_add() {
    if ($("#add-form").find("#idiva_vendita").val() != '') {
        var percentuale = parseFloat($("#add-form").find("#idiva_vendita").selectData().percentuale);
        if(!percentuale) return;

        var input = $("#add-form").find("#prezzo_vendita");
        var prezzo = input.val().toEnglish();

        var scorporato = prezzo * 100 / (100 + percentuale);

        input.val(scorporato);
    }else{
        swal("<?php echo tr('Attenzione'); ?>", "<?php echo tr('Seleziona Iva di vendita.'); ?>", "warning");
    }
}
</script>
