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

$id_original = filter('id_original');

if (!empty($id_record)) {
    include __DIR__.'/init.php';
}

?><form action="<?php
if (isset($id_original)) {
    echo base_path().'/controller.php?id_module='.$id_module;

    if (!empty($id_record)) {
        echo '&id_record='.$id_record;
    }
}
?>" method="post" id="add-form">
	<input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_original" value="<?php echo $id_original; ?>">
    <input type="hidden" name="op" value="<?php echo $id_record ? 'update' : 'add'; ?>">

	<div class="row">
        <div class="col-md-8">
            {[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "title", "required": 1, "value": "$title$" ]}
        </div>
    </div>

	<!-- PULSANTI -->
	<div class="row">
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
