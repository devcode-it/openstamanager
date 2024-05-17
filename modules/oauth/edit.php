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

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('OAuth2'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "value": "$nome$", "disabled": "1" ]}
				</div>

				<div class="col-md-6">
					{[ "type": "checkbox", "label": "<?php echo tr('Abilita'); ?>", "name": "enabled", "value": "$enabled$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('Client ID'); ?>", "name": "client_id", "value": "$client_id$" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('Client Secret'); ?>", "name": "client_secret", "value": "$client_secret$" ]}
                </div>
<?php
            $config = $record['class']::getConfigInputs();
foreach ($config as $name => $field) {
    $field['name'] = 'config['.$name.']';
    $field['value'] = $oauth2->config[$name];

    echo '
				<div class="col-md-4">
					'.input($field).'
				</div>';
}
?>
			</div>
		</div>
	</div>

</form>