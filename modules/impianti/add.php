<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';
use Models\Module;

$id_anagrafica = filter('id_anagrafica');
$id_modulo_anagrafiche = Module::where('name', 'Anagrafiche')->first()->id;
$id_modulo_categorie_impianti = Module::where('name', 'Categorie')->first()->id;
$id_modulo_marca_impianti = Module::where('name', 'Marche')->first()->id;
?><form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "<?php echo tr('Matricola'); ?>", "name": "matricola", "required": 1, "class": "text-center alphanumeric-mask", "maxlength": 25, "validation": "matricola" ]}
		</div>

		<div class="col-md-8">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1 ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "id": "idanagrafica_impianto", "required": 1, "value": "<?php echo $id_anagrafica; ?>", "ajax-source": "clienti", "icon-after": "add|<?php echo $id_modulo_anagrafiche; ?>|tipoanagrafica=Cliente&readonly_tipo=1||<?php echo !empty($id_anagrafica) ? 'disabled' : ''; ?>", "readonly": "<?php echo !empty($id_anagrafica) ? 1 : 0; ?>"  ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede", "value": "$idsede$", "ajax-source": "sedi", "select-options": <?php echo json_encode(['idanagrafica' => $id_anagrafica]); ?>, "placeholder": "Sede legale" ]}
		</div>
		<div class="col-md-4">
			{[ "type": "select", "label": "<?php echo tr('Tecnico predefinito'); ?>", "name": "idtecnico", "ajax-source": "tecnici", "icon-after": "add|<?php echo $id_modulo_anagrafiche; ?>|tipoanagrafica=Tecnico&readonly_tipo=1"  ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-3">
			{[ "type": "select", "label": "<?php echo tr('Categoria'); ?>", "name": "id_categoria", "required": 0, "ajax-source": "categorie_imp", "icon-after": "add|<?php echo $id_modulo_categorie_impianti; ?>|is_impianto=1" ]}
		</div>

		<div class="col-md-3">
			{[ "type": "select", "label": "<?php echo tr('Sottocategoria'); ?>", "name": "id_sottocategoria", "id": "sottocategoria_add", "ajax-source": "sottocategorie_imp", "select-options": <?php echo json_encode(['id_categoria' => $record['id_categoria']]); ?>,"icon-after": "add|<?php echo $id_modulo_categorie_impianti; ?>||hide" ]}
		</div>

        <div class="col-md-3">
            {[ "type": "select", "label": "<?php echo tr('Marca'); ?>", "name": "id_marca", "id": "id_marca_add", "ajax-source": "marche", "icon-after": "add|<?php echo $id_modulo_marca_impianti; ?>" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "select", "label": "<?php echo tr('Modello'); ?>", "name": "id_modello", "id": "id_modello_add", "ajax-source": "modelli", "select-options": <?php echo json_encode(['id_marca' => $record['id_marca']]); ?>, "icon-after": "add|<?php echo $id_modulo_marca_impianti; ?>||hide" ]}
        </div>
	</div>

	<!-- PULSANTI -->
	<div class="modal-footer">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>

<script type="text/javascript">
$(document).ready(function () {
    // Gestione sottocategorie
    var sub = $('#sottocategoria_add');
    var original = sub.parent().find(".input-group-append button").attr("onclick");

    $('#id_categoria').change(function() {
        updateSelectOption("id_categoria", $(this).val());
        session_set('superselect,id_categoria', $(this).val(), 0);

        sub.selectReset();

        if($(this).val()){
            sub.parent().find(".input-group-append button").removeClass("hide");
            sub.parent().find(".input-group-append button").attr("onclick", original.replace('&ajax=yes', "&ajax=yes&id_original=" + $(this).val()));
        }
        else {
            sub.parent().find(".input-group-append button").addClass("hide");
        }
    });

    // Gestione modelli
    var modello = $('#id_modello_add');
    var originalModello = modello.parent().find(".input-group-append button").attr("onclick");

    $('#id_marca_add').change(function() {
        updateSelectOption("id_marca", $(this).val());
        session_set('superselect,id_marca', $(this).val(), 0);

        modello.selectReset();

        if($(this).val()){
            modello.parent().find(".input-group-append button").removeClass("hide");
            modello.parent().find(".input-group-append button").attr("onclick", originalModello ? originalModello.replace(/id_original=\d*/, "id_original=" + $(this).val()) : "openModal('<?php echo tr('Aggiungi modello'); ?>', '<?php echo base_path(); ?>/add.php?id_module=<?php echo $id_modulo_marca_impianti; ?>&id_original=" + $(this).val() + "')");
        }
        else {
            modello.parent().find(".input-group-append button").addClass("hide");
        }
    });

	input('idanagrafica').change(function() {
        updateSelectOption("idanagrafica", $(this).val());
		session_set('superselect,idanagrafica', $(this).val(), 0);

        let value = !input('idanagrafica').get();

        input('idsede').setDisabled(value)
            .getElement().selectReset();
	});
});
</script>