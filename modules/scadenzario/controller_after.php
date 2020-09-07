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

if (empty($dbo->fetchArray('SELECT * FROM co_scadenziario'))) {
    $class = 'muted';
    $disabled = 'disabled';
} else {
    $class = 'primary';
    $disabled = '';
}

?><br>

<!-- STAMPA TOTALE -->
<div class="row">
	<div class="col-md-4 col-md-offset-4">

		<button type="button" onclick="window.open('<?php echo Prints::getHref('Scadenzario', null, null); ?>');" <?php echo $disabled; ?> class="btn btn-<?php echo $class; ?> btn-block btn-lg text-center"><i class="fa fa-print"></i> <?php echo tr('Stampa scadenzario'); ?></button>

	</div>
</div>
<br>
