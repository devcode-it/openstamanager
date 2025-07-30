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
//use Modules\Barcode;
//use Modules\Articoli\Articolo;
use Models\Module;
use Modules\Ubicazioni;

?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Etichetta'); ?>", "name": "u_label", "required": 1, "value": "$u_label$" ]}
				</div>
				<!--<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "u_label_info", "value": "$u_label_info$" ]}
				</div>
				<div class="col-md-8">
					{[ "type": "text", "label": "<?php echo tr('Note'); ?>", "name": "u_notes", "value": "$u_notes$" ]}
				</div>-->
				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "title", "value": "$title$" ]}
				</div>
				<div class="col-md-8">
					{[ "type": "text", "label": "<?php echo tr('Note'); ?>", "name": "notes", "value": "$notes$" ]}
				</div>
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Tags'); ?>", "name": "u_tags", "value": "$u_tags$" ]}
				</div>
				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo tr('Colore'); ?>", "name": "colore", "class": "colorpicker text-center", "value": "$colore$", "extra": "maxlength='7'", "icon-after": "<div class='img-circle square'></div>" ]}
				</div>
			</div>
			<a  class="btn btn-info"  href="/osm/pdfgen.php?id_print=56&id_record=<?php echo $record['id']; ?>" target="_blank" id="print-button">
				<i class="fa fa-print"> Stampa Etichetta</i>
			</a>
			<a  class="btn btn-info"  href="/osm/pdfgen.php?id_print=58&id_record=<?php echo $record['id']; ?>" target="_blank" id="print-button">
				<i class="fa fa-print"> Stampa Etichetta BIG</i>
			</a>
		</div>
	</div>

</form>

<script>
    $(document).ready(function() {
        $('.colorpicker').colorpicker({ format: 'hex' }).on('changeColor', function() {
            $(this).parent().find('.square').css('background', $(this).val());
        });
        $('.colorpicker').parent().find('.square').css('background', $('.colorpicker').val());
    });
</script>

					
<?php

$righe = $dbo->fetchNum('SELECT `id` FROM `mg_articoli` WHERE `ubicazione`='.prepare($record['u_label']));
if (!empty($righe)) {
    echo '
<div class="alert alert-danger">
    '.tr('Ci sono _NUM_ righe collegate', [
        '_NUM_' => $righe,
    ]).'
</div>';
}

$elementi = $dbo->fetchArray('SELECT `id`,`codice`,`barcode`,`ubicazione` FROM `mg_articoli` WHERE `ubicazione`='.prepare($record['u_label']));
echo '<div class="alert alert-danger">';
   foreach ($elementi as $elemento) {
	$descrizione = tr('Articolo _CODICE_', [
		'_CODICE_' => !empty($elemento['codice']) ? $elemento['codice'] : $elemento['barcode'],
	]);
	$modulo = 'Articoli';
	$id = $elemento['id'];
      echo '<li>'.Modules::link($modulo, $id, $descrizione).'</li>';
   }
echo '</div>';

?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

