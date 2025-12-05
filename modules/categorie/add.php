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
use Modules\Articoli\Categoria;

$id_original = filter('id_original');
$is_impianto_default = filter('is_impianto') !== null ? filter('is_impianto') : 0;
$is_articolo_default = filter('is_articolo') !== null ? filter('is_articolo') : 1;

if (!empty($id_record)) {
    include __DIR__.'/init.php';
}

?><form action="<?php
if (isset($id_original)) {
    echo base_path_osm().'/controller.php?id_module='.$id_module;

    if (!empty($id_record)) {
        echo '&id_record='.$id_record;
    }
}
?>" method="post" id="add-form">
	<input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_original" value="<?php echo $id_original; ?>">
    <input type="hidden" name="op" value="<?php echo $id_record ? 'update' : 'add'; ?>">
    <?php if (!empty($id_original)) { ?>
    <input type="hidden" name="is_articolo" value="<?php echo $id_original ? Categoria::find($id_original)->is_articolo : 1; ?>">
    <input type="hidden" name="is_impianto" value="<?php echo $id_original ? Categoria::find($id_original)->is_impianto : 0; ?>">
    <?php } ?>

	<div class="row">
        <div class="col-md-5">
            {[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome_add", "required": 1, "value": "$title$" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "text", "label": "<?php echo tr('Colore'); ?>", "name": "colore_add", "id": "colore", "class": "colorpicker text-center", "value": "<?php echo $categoria->colore; ?>", "extra": "maxlength=\"7\"", "icon-after": "<div class='img-circle square'></div>" ]}
        </div>

        <div class="col-md-2">
            {[ "type": "checkbox", "label": "<?php echo tr('Articolo'); ?>", "name": "is_articolo_add", "value": "<?php echo $categoria ? $categoria->is_articolo : ($id_original ? Categoria::find($id_original)->is_articolo : $is_articolo_default); ?>", "disabled": "<?php echo !empty($id_original) ? 1 : 0; ?>" ]}
        </div>

        <div class="col-md-2">
            {[ "type": "checkbox", "label": "<?php echo tr('Impianto'); ?>", "name": "is_impianto_add", "value": "<?php echo $categoria ? $categoria->is_impianto : ($id_original ? Categoria::find($id_original)->is_impianto : $is_impianto_default); ?>", "disabled": "<?php echo !empty($id_original) ? 1 : 0; ?>" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "<?php echo tr('Nota'); ?>", "name": "nota_add", "value": "<?php echo $categoria ? $categoria->getTranslation('note') : ''; ?>", "rows": "3" ]}
        </div>
    </div>



	<!-- PULSANTI -->
	<div class="modal-footer">
		<div class="col-md-12 text-right">
	<?php
if (!empty($id_record)) {
    ?>
			<button type="submit" class="btn btn-success"><i class="fa fa-save"></i> <?php echo tr('Salva'); ?></button>
<?php
} else {
    ?>
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
<?php
}
?>
		</div>
	</div>
</form>

<script>
		$(document).ready( function() {
			$('#modals > div .colorpicker').colorpicker({ format: 'hex' }).on('changeColor', function() {
				$('#modals > div #colore_').parent().find('.square').css('background', $('#modals > div #colore_').val());
			});

			$('#modals > div #colore_').parent().find('.square').css('background', $('#modals > div #colore_').val());

            $('#modals > div .colorpicker').colorpicker({ format: 'hex' }).on('changeColor', function() {
				$('#modals > div #colore_').parent().find('.square').css('background', $('#modals > div #colore_').val());
			});

			$('#modals > div #colore_').parent().find('.square').css('background', $('#modals > div #colore_').val());
		});
</script>
