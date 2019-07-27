<?php

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
