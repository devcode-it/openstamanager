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

$id_modulo_categorie_impianti = Module::where('name', 'Categorie impianti')->first()->id;
$id_modulo_marca_impianti = Module::where('name', 'Marche impianti')->first()->id;

$img = null;
if (!empty($record['immagine'])) {
    $fileinfo = Uploads::fileInfo($record['immagine']);

    $default_img = '/'.Uploads::getDirectory($id_module).'/'.$fileinfo['filename'].'_thumb600.'.$fileinfo['extension'];

    $img = file_exists(base_dir().$default_img) ? base_path().$default_img : base_path().'/'.Uploads::getDirectory($id_module).'/'.$record['immagine'];
}

?><form action="" method="post" id="edit-form" enctype="multipart/form-data">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="matricola" value="<?php echo $id_record; ?>">

	<!-- DATI ANAGRAFICI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Dati impianto'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-3">
					{[ "type": "image", "label": "<?php echo tr('Immagine'); ?>", "name": "immagine", "class": "img-thumbnail", "value": "<?php echo $img; ?>" ]}
				</div>

				<div class="col-md-9">
					<div class="row">
						<div class="col-md-4">
							{[ "type": "text", "label": "<?php echo tr('Matricola'); ?>", "name": "matricola", "required": 1, "class": "text-center", "maxlength": 25, "value": "$matricola$", "validation": "matricola" ]}
						</div>

						<div class="col-md-6">
							{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
						</div>
						<div class="col-md-2">
							{[ "type": "date", "label": "<?php echo tr('Data installazione'); ?>", "name": "data", "value": "$data$" ]}
						</div>
						<div class="clearfix"></div>

						<div class="col-md-4">
							<?php echo Modules::link('Anagrafiche', $record['idanagrafica'], null, null, 'class="pull-right"'); ?>
							{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "value": "$idanagrafica$", "extra": "", "ajax-source": "clienti" ]}
						</div>
						<?php
                        echo '
						<div class="col-md-4">
							{[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede", "value": "$idsede$", "required": "1", "ajax-source": "sedi", "select-options": '.json_encode(['idanagrafica' => $record['idanagrafica']]).', "placeholder": "'.tr('Sede legale').'" ]}
						</div>';
?>
						<div class="col-md-4">
							{[ "type": "select", "label": "<?php echo tr('Tecnico predefinito'); ?>", "name": "idtecnico", "ajax-source": "tecnici", "value": "$idtecnico$" ]}
						</div>
					</div>					
					<div class ="row">
						<div class="col-md-4">
							{[ "type": "select", "label": "<?php echo tr('Marca'); ?>", "name": "id_marca", "value": "$id_marca$", "ajax-source": "marca", "icon-after": "add|<?php echo $id_modulo_marca_impianti; ?>" ]}
						</div>
						<div class="col-md-4">
							{[ "type": "select", "label": "<?php echo tr('Modello'); ?>", "name": "id_modello", "value": "$id_modello$","ajax-source": "modello", "select-options": <?php echo json_encode(['id_marca' => $record['id_marca']]); ?>, "icon-after": "add|<?php echo $id_modulo_marca_impianti; ?>|id_original=<?php echo $record['id_marca']; ?>" ]}
						</div>
						<div class="col-md-4">
							{[ "type": "text", "label": "<?php echo tr('Proprietario'); ?>", "name": "proprietario", "value": "$proprietario$" ]}
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
                            <?php echo (!empty($record['id_categoria'])) ?
    Modules::link('Categorie impianti', $record['id_categoria'], null, null, 'class="pull-right"') : ''; ?>
                            {[ "type": "select", "label": "<?php echo tr('Categoria'); ?>", "name": "id_categoria", "required": 0, "value": "$id_categoria$", "ajax-source": "categorie_imp", "icon-after": "add|<?php echo $id_modulo_categorie_impianti; ?>" ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "select", "label": "<?php echo tr('Sottocategoria'); ?>", "name": "id_sottocategoria", "value": "$id_sottocategoria$", "ajax-source": "sottocategorie_imp", "select-options": <?php echo json_encode(['id_categoria' => $record['id_categoria']]); ?>, "icon-after": "add|<?php echo $id_modulo_categorie_impianti; ?>|id_original=<?php echo $record['id_categoria']; ?>" ]}
                        </div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "value": "$descrizione$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Ubicazione'); ?>", "name": "ubicazione", "value": "$ubicazione$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Palazzo'); ?>", "name": "palazzo", "value": "$palazzo$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Scala'); ?>", "name": "scala", "value": "$scala$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Piano'); ?>", "name": "piano", "value": "$piano$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Interno'); ?>", "name": "interno", "value": "$interno$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Occupante'); ?>", "name": "occupante", "value": "$occupante$" ]}
				</div>
			</div>

		</div>
	</div>
</form>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<?php
$elementi = $dbo->fetchArray('SELECT `in_interventi`.`id`, `codice` AS numero, `data_richiesta` AS data, "Intervento" AS tipo_documento FROM `in_interventi` INNER JOIN `my_impianti_interventi` ON `in_interventi`.`id`=`my_impianti_interventi`.`idintervento` WHERE `my_impianti_interventi`.`idimpianto` = '.prepare($id_record).'
UNION 
SELECT `co_contratti`.`id`, `numero` AS numero, `data_bozza` AS data, "Contratto" AS tipo_documento FROM `co_contratti` INNER JOIN `my_impianti_contratti` ON `co_contratti`.`id`=`my_impianti_contratti`.`idcontratto` WHERE `my_impianti_contratti`.`idimpianto` = '.prepare($id_record));
$class = '';

if (!empty($elementi)) {
    echo '
<div class="card card-warning collapsable collapsed-card">
    <div class="card-header with-border">
        <h3 class="card-title"><i class="fa fa-warning"></i> '.tr('Documenti collegati: _NUM_', [
        '_NUM_' => count($elementi),
    ]).'</h3>
        <div class="card-tools pull-right">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <ul>';

    foreach ($elementi as $elemento) {
        $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
            '_DOC_' => $elemento['tipo_documento'],
            '_NUM_' => $elemento['numero'],
            '_DATE_' => Translator::dateToLocale($elemento['data']),
        ]);

        if ($elemento['tipo_documento'] == 'Intervento') {
            $modulo = 'Interventi';
        } else {
            $modulo = 'Contratti';
        }
        $id = $elemento['id'];

        echo '
            <li>'.Modules::link($modulo, $id, $descrizione).'</li>';
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
?>

<script type="text/javascript">
$(document).ready(function() {
	$('#idanagrafica').change(function() {
        updateSelectOption("idanagrafica", $(this).val());
		session_set('superselect,idanagrafica', $(this).val(), 0);

        var value = !$(this).val();

		$("#idsede").prop("disabled", value)
            .selectReset();
	});

	$("#id_categoria").on("change", function() {
		updateSelectOption("id_categoria", $(this).val());
		$("#id_sottocategoria").val(null).trigger("change");
	});

	$("#id_marca").on("change", function() {
		updateSelectOption("id_marca", $(this).val());
		$("#id_modello").val(null).trigger("change");
	});

});
</script>
