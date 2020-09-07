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

$skip_permissions = true;
include_once __DIR__.'/../../core.php';

$resource = filter('resource');

$user = Auth::user();
$utente = $user->toArray();

echo '
<form action="" method="post" enctype="multipart/form-data" id="user_update">
	<input type="hidden" name="op" value="self_update">';

if ($resource == 'password') {
    include $structure->filepath('components/password.php');
} elseif ($resource == 'photo') {
    include $structure->filepath('components/photo.php');
}

echo '
    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="sumbit" onclick="submitCheck()" class="btn btn-primary" id="submit-button">
			    <i class="fa fa-edit"></i> '.tr('Modifica').'
            </button>
		</div>
	</div>
</form>

<script>$(document).ready(init)</script>';
