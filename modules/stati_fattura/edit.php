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
?>

<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$title$", "extra": "readonly" ]}
		</div>

		<div class="col-md-3">
			{[ "type": "text", "label": "<?php echo tr('Icona'); ?>", "name": "icona", "required": 1, "class": "text-center", "value": "$icona$", "extra": "", "icon-after": "<?php echo (!empty($record['icona'])) ? '<i class=\"'.$record['icona'].'\"></i>' : ''; ?>"  ]}
		</div>

		<div class="col-md-2">
			{[ "type": "text", "label": "<?php echo tr('Colore'); ?>", "name": "colore", "required": 1, "class": "colorpicker text-center", "value": "$colore$", "extra": "maxlength='7'", "icon-after": "<div class='img-circle square'></div>" ]}
		</div>

	</div>
</form>


<?php
$documenti = $dbo->fetchNum('SELECT id FROM co_documenti WHERE idstatodocumento='.prepare($id_record));

if (!empty($documenti)) {
    echo '
<div class="alert alert-danger">
    '.tr('Ci sono _NUM_ documenti collegati', [
        '_NUM_' => $documenti,
    ]).'.
</div>';
}

if (!empty($record['can_delete'])) {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
	<i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
}

?>
<script>
    $(document).ready(function() {
        $('.colorpicker').colorpicker({ format: 'hex' }).on('changeColor', function() {
            $(this).parent().find('.square').css('background', $(this).val());
        });
        $('.colorpicker').parent().find('.square').css('background', $('.colorpicker').val());
		notifica();
	});

</script>