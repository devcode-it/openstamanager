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
use Modules\Articoli\Marca;

$id_anagrafica = filter('id_anagrafica');
$id_original = filter('id_original');

if (!empty($id_record)) {
    include __DIR__.'/init.php';
}

?><form action="<?php
echo base_path().'/controller.php?id_module='.$id_module;

if (!empty($id_record)) {
    echo '&id_record='.$id_record;
}
?>" method="post" id="add-form">
	<input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_original" value="<?php echo $id_original; ?>">
    <input type="hidden" name="op" value="<?php echo $id_record ? 'update' : 'add'; ?>">
    <?php if (!empty($id_original)) { ?>
    <input type="hidden" name="is_articolo" value="<?php echo $id_original ? Marca::find($id_original)->is_articolo : 1; ?>">
    <input type="hidden" name="is_impianto" value="<?php echo $id_original ? Marca::find($id_original)->is_impianto : 0; ?>">
    <?php } ?>

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "name", "required": 1, "value":"$name$"]}
		</div>
		<div class="col-md-4">
			{[ "type": "text", "label": "<?php echo tr('Link produttore'); ?>", "name": "link", "value":"$link$"]}
		</div>
		<div class="col-md-2">
            {[ "type": "checkbox", "label": "<?php echo tr('Articolo'); ?>", "name": "is_articolo_add", "value": "<?php echo $marca ? $marca->is_articolo : ($id_original ? Marca::find($id_original)->is_articolo : 1); ?>", "disabled": "<?php echo !empty($id_original) ? 1 : 0; ?>" ]}
        </div>

        <div class="col-md-2">
            {[ "type": "checkbox", "label": "<?php echo tr('Impianto'); ?>", "name": "is_impianto_add", "value": "<?php echo $marca ? $marca->is_impianto : ($id_original ? Marca::find($id_original)->is_impianto : 0); ?>", "disabled": "<?php echo !empty($id_original) ? 1 : 0; ?>" ]}
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