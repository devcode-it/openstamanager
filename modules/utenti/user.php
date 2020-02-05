<?php

include_once __DIR__.'/../../core.php';

use Models\Group;
use Models\User;

Permissions::check('rw');

$id_utente = filter('id_utente');
$user = User::find($id_utente);
$utente = $user ? $user->toArray() : [];

// Gruppo della selezione
if (!empty($id_record)) {
    $gruppo_utente = Group::find($id_record)->descrizione;

    $gruppi = [
        'Clienti' => 'Cliente',
        'Tecnici' => 'Tecnico',
        'Agenti' => 'Agente',
    ];
    $gruppo = $gruppi[$gruppo_utente];
}

// Lettura sedi dell'utente già impostate
if (!empty($user)) {
    $sedi = $dbo->fetchOne('SELECT GROUP_CONCAT(idsede) as sedi FROM zz_user_sedi WHERE id_user='.prepare($id_utente).' GROUP BY id_user')['sedi'];
}

echo '
<form action="'.ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'"  method="post" enctype="multipart/form-data" id="user_update">
	<input type="hidden" name="op" value="update_user">
	<input type="hidden" name="backto" value="record-edit">
	
	<input type="hidden" name="id_utente" value="'.$utente['id'].'">';

include $structure->filepath('components/photo.php');
include $structure->filepath('components/base.php');

if (!empty($user)) {
    echo '
	<div class="row">
		<div class="col-md-12">
		    {[ "type": "checkbox", "label": "'.tr('Cambia password').'", "name": "change_password", "value": "0" ]}
		</div>
    </div>
    
    <script>
        function no_check_pwd(){
            $("#password").attr("disabled", true);
            $("#submit-button").attr("disabled", false).removeClass("disabled");
        }
        
        $("#modals > div").on("shown.bs.modal", function () {
            no_check_pwd();
        });

        $("#change_password").change(function() {
            if (this.checked) {
                $("#password").attr("disabled", false);
                $("#password").change();
            } else {
                no_check_pwd();
            }
        });
    </script>';
}

include $structure->filepath('components/password.php');

echo '
    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="button" onclick="submitCheck()" class="btn btn-primary" id="submit-button">';

if (empty($user)) {
    echo '
			    <i class="fa fa-plus"></i> '.tr('Aggiungi');
} else {
    echo '
			    <i class="fa fa-edit"></i> '.tr('Modifica');
}

echo '
            </button>
		</div>
	</div>
</form>	

<script>
function submitCheck() {
    var username = parseInt($("#username").attr("valid"));
        
    if(username) {
        $("#user_update").submit();
    }
}
</script>

<script>$(document).ready(init)</script>';
