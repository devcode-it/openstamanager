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

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-5">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$title$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Colore'); ?>", "name": "colore", "class": "colorpicker text-center", "value": "$colore$", "extra": "maxlength='7'", "icon-after": "<div class='img-circle square'></div>" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "checkbox", "label": "<?php echo tr('Articolo'); ?>", "name": "is_articolo", "value": "<?php echo $categoria->is_articolo; ?>" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "checkbox", "label": "<?php echo tr('Impianto'); ?>", "name": "is_impianto", "value": "<?php echo $categoria->is_impianto; ?>" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Nota'); ?>", "name": "nota", "value": "<?php echo $categoria->getTranslation('note'); ?>", "rows": "3" ]}
				</div>
			</div>

		</div>
	</div>

</form>

<div class="card card-primary">
	<div class="card-header">
		<h3 class="card-title"><?php echo tr('Sottocategorie'); ?></h3>
	</div>

	<div class="card-body">
		<div class="pull-left">
			<a class="btn btn-primary" data-href="<?php echo base_path(); ?>/add.php?id_module=<?php echo $id_module; ?>&id_original=<?php echo $id_record; ?>" data-card-widget="modal" data-title="<?php echo tr('Aggiungi riga'); ?>"><i class="fa fa-plus"></i> <?php echo tr('Sottocategoria'); ?></a><br>
		</div>
		<div class="clearfix"></div>
		<hr>

		<div class="row">
			<div class="col-md-12">
				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered">
						<thead>
							<tr>
								<th width="25%" class="text-left"><?php echo tr('Nome'); ?></th>
								<th width="15%" class="text-center"><?php echo tr('Colore'); ?></th>
								<th width="10%" class="text-center"><?php echo tr('Articoli'); ?></th>
								<th width="10%" class="text-center"><?php echo tr('Impianti'); ?></th>
								<th class="text-left"><?php echo tr('Nota'); ?></th>
								<th width="10%" class="text-center"><?php echo tr('Azioni'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php include base_dir().'/modules/'.Module::find($id_module)->directory.'/row-list.php'; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
    $(document).ready(function() {
        $('.colorpicker').colorpicker({ format: 'hex' }).on('changeColor', function() {
            $(this).parent().find('.square').css('background', $(this).val());
        });
        $('.colorpicker').parent().find('.square').css('background', $('.colorpicker').val());
    });
</script>
<?php

// Articoli collegati alla categoria
$articoli = $dbo->fetchArray('SELECT `mg_articoli`.`id`, `mg_articoli`.`codice`, `mg_articoli`.`barcode`, `sottocategorie_lang`.`title` AS sottocategoria FROM `mg_articoli` LEFT JOIN `zz_categorie` AS `sottocategorie` ON `mg_articoli`.`id_sottocategoria` = `sottocategorie`.`id` LEFT JOIN `zz_categorie_lang` AS `sottocategorie_lang` ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE (`id_categoria`='.prepare($id_record).' OR `id_sottocategoria`='.prepare($id_record).' OR `id_sottocategoria` IN (SELECT `id` FROM `zz_categorie` WHERE `parent`='.prepare($id_record).')) AND `deleted_at` IS NULL');

// Impianti collegati alla categoria
$impianti = $dbo->fetchArray('SELECT `my_impianti`.`id`, `my_impianti`.`matricola`, `my_impianti`.`nome`, `sottocategorie_lang`.`title` AS sottocategoria FROM `my_impianti` LEFT JOIN `zz_categorie` AS `sottocategorie` ON `my_impianti`.`id_sottocategoria` = `sottocategorie`.`id` LEFT JOIN `zz_categorie_lang` AS `sottocategorie_lang` ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE (`id_categoria`='.prepare($id_record).' OR `id_sottocategoria`='.prepare($id_record).'  OR `id_sottocategoria` IN (SELECT `id` FROM `zz_categorie` WHERE `parent`='.prepare($id_record).'))');

// Visualizzazione degli articoli collegati
if (!empty($articoli)) {
    echo '
<div class="card card-info collapsable collapsed-card">
    <div class="card-header with-border">
        <h3 class="card-title"><i class="fa fa-cube"></i> '.tr('Articoli collegati: _NUM_', [
        '_NUM_' => count($articoli),
    ]).'</h3>
        <div class="card-tools pull-right">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="list-group">';

    foreach ($articoli as $elemento) {
        $codice = !empty($elemento['codice']) ? $elemento['codice'] : $elemento['barcode'];
        $descrizione = tr('Articolo _CODICE_', [
            '_CODICE_' => $codice,
        ]);

        // Aggiunge la sottocategoria se presente
        if (!empty($elemento['sottocategoria'])) {
            $descrizione .= ' <small class="text-primary">('.$elemento['sottocategoria'].')</small>';
        }

        $modulo = 'Articoli';
        $id = $elemento['id'];

        echo '
		<a class="list-group-item list-group-item-action" href="'.base_path().'/editor.php?id_module='.Module::where('name', $modulo)->first()->id.'&id_record='.$id.'">
				<i class="fa fa-cube"></i> '.$descrizione.'
			</a>';
    }

    echo '
	</div>
            </div>
        </div>
    </div>
</div>';
}

// Visualizzazione degli impianti collegati
if (!empty($impianti)) {
    echo '
<div class="card card-primary collapsable collapsed-card">
    <div class="card-header with-border">
        <h3 class="card-title"><i class="fa fa-industry"></i> '.tr('Impianti collegati: _NUM_', [
        '_NUM_' => count($impianti),
    ]).'</h3>
        <div class="card-tools pull-right">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="list-group">';

    foreach ($impianti as $elemento) {
        $descrizione = tr('Impianto _MATRICOLA_', [
            '_MATRICOLA_' => !empty($elemento['matricola']) ? $elemento['matricola'].' - '.$elemento['nome'] : $elemento['nome'],
        ]);

        // Aggiunge la sottocategoria se presente
        if (!empty($elemento['sottocategoria'])) {
            $descrizione .= ' <small class="text-primary">('.$elemento['sottocategoria'].')</small>';
        }

        $modulo = 'Impianti';
        $id = $elemento['id'];

        echo '
		<a class="list-group-item list-group-item-action" href="'.base_path().'/editor.php?id_module='.Module::where('name', $modulo)->first()->id.'&id_record='.$id.'">
				<i class="fa fa-industry"></i> '.$descrizione.'
			</a>';
    }

    echo '
		</div>
            </div>
        </div>
    </div>
</div>';
}

// Pulsante elimina se non ci sono elementi collegati
if (empty($articoli) && empty($impianti)) {
    echo '
    <a class="btn btn-danger ask" data-backto="record-list">
        <i class="fa fa-trash"></i> '.tr('Elimina').'
    </a>';
}
