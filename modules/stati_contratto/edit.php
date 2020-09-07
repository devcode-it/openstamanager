<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

if ($record['can_delete']) {
    $attr = '';
} else {
    $attr = 'readonly';
    echo '<div class="alert alert-warning">'.tr('Alcune impostazioni non possono essere modificate per questo stato.').'</div>';
}

?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<div class="row">
		<div class="col-md-5">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$", "extra": "<?php echo $attr; ?>" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "text", "label": "<?php echo tr('Icona'); ?>", "name": "icona", "required": 1, "class": "text-center", "value": "$icona$", "extra": "", "icon-after": "<?php echo (!empty($record['icona'])) ? '<i class=\"'.$record['icona'].'\"></i>' : ''; ?>"  ]}
		</div>

		<div class="col-md-3">
		 	<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><?php echo tr('Flags'); ?></h3>
				</div>

				<div class="panel-body">
		            {[ "type": "checkbox", "label": "<?php echo tr('Completato?'); ?>", "name": "is_completato", "value": "$is_completato$", "help": "<?php echo tr('I contratti che si trovano in questo stato verranno considerati come completati'); ?>", "placeholder": "<?php echo tr('Completato'); ?>", "extra": "" ]}
		            {[ "type": "checkbox", "label": "<?php echo tr('Pianificabile?'); ?>", "name": "is_pianificabile", "value": "$is_pianificabile$", "help": "<?php echo tr('I contratti che si trovano in questo stato verranno considerati come pianificabili'); ?>", "placeholder": "<?php echo tr('Pianificabile'); ?>", "extra": "" ]}
		            {[ "type": "checkbox", "label": "<?php echo tr('Fatturabile?'); ?>", "name": "is_fatturabile", "value": "$is_fatturabile$", "help": "<?php echo tr('I contratti che si trovano in questo stato verranno considerati come fatturabili'); ?>", "placeholder": "<?php echo tr('Fatturabile'); ?>", "extra": "" ]}
				</div>
			</div>
		</div>
	</div>
</form>


<?php

if (!empty($contratti)) {
    echo '
<div class="alert alert-danger">
    '.tr('Ci sono _NUM_ contratti collegati', [
        '_NUM_' => $contratti,
    ]).'.
</div>';
}

if (!empty($record['can_delete'])) {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
	<i class="fa fa-trash"></i>'.tr('Elimina').'
</a>';
}

?>
