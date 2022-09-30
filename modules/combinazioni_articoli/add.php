<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" id="add-form">
    <input type="hidden" name="op" value="add">
    <input type="hidden" name="backto" value="record-edit">



    <div class="row">
        <div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Codice').'", "name": "codice", "required": 1, "help": "'.tr('Codice di base per la combinazione: alla generazione variante vengono aggiunti i valore degli Attributi relativi').'" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Nome').'", "name": "nome", "required": 1, "help": "'.tr('Nome univoco della combinazione').'" ]}
		</div>
    </div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Categoria').'", "name": "id_categoria", "required": 0, "value": "$id_categoria$", "ajax-source": "categorie", "icon-after": "add|'.Modules::get('Categorie articoli')['id'].'" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Sottocategoria').'", "name": "id_sottocategoria", "value": "$id_sottocategoria$", "ajax-source": "sottocategorie", "select-options": '.json_encode(['id_categoria' => $record['id_categoria']]).' ]}
        </div>
    </div>

    <div class="row">
		<div class="col-md-12">
			{[ "type": "select", "label": "'.tr('Attributi').'", "name": "attributi[]", "values": "query=SELECT id, nome AS descrizione FROM mg_attributi WHERE deleted_at IS NULL", "required": 1, "multiple": 1, "help": "'.tr('Attributi abilitati per la combinazione corrente').'" ]}
		</div>
    </div>

    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
        </div>
    </div>
</form>

<script>
$("#id_categoria").change(function() {
    updateSelectOption("id_categoria", $(this).val());

    $("#id_sottocategoria").val(null).trigger("change");
});
</script>';
