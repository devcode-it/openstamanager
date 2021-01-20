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

// Se lo stato intervento è uno di quelli di default, non lo lascio modificare
if ($record['default']) {
    $attr = "readonly='true'";
    $warning_text = '<div class="alert alert-warning">'.tr('Non puoi modificare questo tipo di anagrafica!').'</div>';
} else {
    $attr = '';
    $warning_text = '';
}

// Se il tipo di anagrafica è uno di quelli di default, non lo lascio modificare
if (!empty($record['default'])) {
    // Disabilito il pulsante di salvataggio
    echo '
    <script>
    $(document).ready(function() {
        $("#save").prop("disabled", true).addClass("disabled");
    });
    </script>

    '.$warning_text;
}

?>

<form action="" method="post">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<div class="row">

		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$", "extra": "<?php echo $attr; ?>" ]}
		</div>

	</div>
</form>

<?php

// Se il tipo di anagrafica è uno di quelli di default, non lo lascio modificare
if (empty($record['default'])) {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
}
