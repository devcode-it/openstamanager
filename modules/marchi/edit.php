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


?><form action="" method="post" id="edit-form" enctype="multipart/form-data">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="matricola" value="<?php echo $id_record; ?>">

	<!-- DATI ANAGRAFICI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="panel-title"><?php echo tr('Dati marchio'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('name'); ?>", "name": "name", "value":"$name$", "required": 1, "validation": "name" ]}
				</div>
			</div>

		</div>
	</div>
</form>

<?php
$elementi = $dbo->fetchArray('SELECT `mg_articoli`.`id`, CONCAT(`mg_articoli`.`codice`, " - ", `mg_articoli_lang`.`title`) AS `descrizione` FROM `mg_articoli` INNER JOIN mg_articoli_lang ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `mg_articoli`.`id_marchio` = '.prepare($id_record));
$class = '';

if (!empty($elementi)) {
    echo '
<div class="card card-warning collapsable collapsed-card">
    <div class="card-header with-border">
        <h3 class="card-title"><i class="fa fa-warning"></i> '.tr('Articoli collegati: _NUM_', [
            '_NUM_' => count($elementi),
        ]).'</h3>
        <div class="card-tools pull-right">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <ul>';

    foreach ($elementi as $elemento) {
        echo '
            <li>'.Modules::link('Articoli', $elemento['id'], $elemento['descrizione']).'</li>';
    }
    $class = 'disabled';

    echo '
        </ul>
    </div>
</div>';
}

echo '
<a class="btn btn-danger ask '.$class.'" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';