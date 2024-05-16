<?php

include_once __DIR__.'/../../core.php';
use Models\Module;

$numero_varianti = $combinazione->articoli()->count();

echo '
<form action="" method="post" id="edit-form" enctype="multipart/form-data">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

    <div class="card card-primary">
        <div class="card-heading">
            <h3 class="card-title">'.tr('Dati').'</h3>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Codice').'", "name": "codice", "value": "'.$combinazione->codice.'", "required": 1, "help": "'.tr('Codice di base per la combinazione: alla generazione variante vengono aggiunti i valore degli Attributi relativi').'" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Nome').'", "name": "nome", "value": "'.$combinazione->getTranslation('title').'", "required": 1, "help": "'.tr('Nome univoco della combinazione').'" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">';
if (!empty($combinazione->id_categoria)) {
    echo '
                    '.Modules::link('Categorie articoli', $combinazione->id_categoria, null, null, 'class="pull-right"');
}
echo '
                    {[ "type": "select", "label": "'.tr('Categoria').'", "name": "id_categoria", "required": 0, "value": "$id_categoria$", "ajax-source": "categorie", "icon-after": "add|'.(new Module())->getByField('title', 'Categorie articoli', Models\Locale::getPredefined()->id).'" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "select", "label": "'.tr('Sottocategoria').'", "name": "id_sottocategoria", "value": "$id_sottocategoria$", "ajax-source": "sottocategorie", "select-options": '.json_encode(['id_categoria' => $combinazione->id_categoria]).' ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "select", "label": "'.tr('Attributi').'", "name": "attributi[]", "value": "'.implode(',', $combinazione->attributi->pluck('id')->all()).'", "values": "query=SELECT `mg_attributi`.`id`, `mg_attributi_lang`.`title` AS descrizione FROM `mg_attributi` LEFT JOIN `mg_attributi_lang` ON (`mg_attributi_lang`.`id_record` = `mg_attributi`.`id` AND `mg_attributi_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `deleted_at` IS NULL", "required": 1, "multiple": 1, "help": "'.tr('Attributi abilitati per la combinazione corrente').'" ]}
                </div>
            </div>
        </div>
    </div>
</form>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">'.tr('Varianti disponibili (Articoli)').'</h3>
    </div>

    <div class="card-body">
        <button type="button" class="btn btn-primary" onclick="aggiungiVariante(this)">
            <i class="fa fa-plus"></i> '.tr('Aggiungi variante').'
        </button>

        <div class="tip pull-right " data-card-widget="tooltip" title="'.tr('Genera tutte le varianti sulla base degli Attributi associati alla Combinazione corrente').'. '.tr('Disponibile solo se non sono giò presenti delle varianti').'.">
             <button type="button" class="btn btn-warning '.($numero_varianti === 0 ? '' : 'disabled').'" onclick="generaVarianti(this)">
                <i class="fa fa-refresh"></i> '.tr('Genera tutte le varianti').'
            </button>
        </div>

        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th width="10%">'.tr('Foto').'</th>
                    <th>'.tr('Variante').'</th>
                    <th>'.tr('Articolo').'</th>
                    <th class="text-center" width="13%"></th>
                </tr>
            </thead>

            <tbody>';

$articoli = $combinazione->articoli;
foreach ($articoli as $articolo) {
    echo '
                <tr data-id="'.$articolo->id.'">
                    <td><img class="img-thumbnail img-responsive" src="'.$articolo->image.'"></td>
                    <td>'.$articolo->nome_variante.'</td>
                    <td>'.Modules::link('Articoli', $articolo->id, $articolo->codice.' - '.$articolo->getTranslation('title')).'</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-warning btn-xs" onclick="modificaVariante('.$articolo->id.')">
                            <i class="fa fa-edit"></i> '.tr('Modifica').'
                        </button>
                        <button type="button" class="btn btn-danger btn-xs" onclick="rimuoviVariante('.$articolo->id.')">
                            <i class="fa fa-remove"></i> '.tr('Rimuovi').'
                        </button>
                    </td>
                </tr>';
}

echo '
            </tbody>
        </table>
    </div>
</div>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>

<script>
function aggiungiVariante(button) {
    // Apertura modal
    openModal("'.tr('Aggiungi variante').'", "'.$module->fileurl('add-variante.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record);
}

function modificaVariante(id) {
    // Modifica modal
    openModal("'.tr('Modifica variante').'", "'.$module->fileurl('edit-variante.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&id_articolo=" + id);
}

function rimuoviVariante(id) {
    if( confirm(\'Rimuovere la variante dalla combinazione?\') ){ 
        $.post( \''.base_path().'/modules/combinazioni_articoli/actions.php\', { op: \'remove-variante\', id_articolo: + id }, function(data){ location.href=\''.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'\'; } ); 
    }
}

function generaVarianti(button) {
    // Redirect
    redirect(globals.rootdir + "/editor.php", {
       id_module: globals.id_module,
       id_record: globals.id_record,
       op: "genera-varianti",
       backto: "record-edit",
   });
}

$("#id_categoria").change(function() {
    updateSelectOption("id_categoria", $(this).val());

    $("#id_sottocategoria").val(null).trigger("change");
});
</script>';
