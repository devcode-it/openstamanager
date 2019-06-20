<?php

include_once __DIR__.'/../../core.php';

// Lunghezza minima della password
$min_length_password = 8;
// Lunghezza minima del nome utente (username)
$min_length_username = 4;

$self_edit = Modules::getPermission('Utenti e permessi') != 'rw' || (filter('id_utente') == null && filter('idgruppo') == null);

if ($self_edit) {
    $user = Auth::user();

    $id_utente = $user['id'];
} else {
    $idgruppo = intval(filter('idgruppo'));
    $id_utente = filter('id_utente');

    //gruppo della selezione
    $nome_gruppo = $dbo->fetchArray('SELECT nome FROM zz_groups WHERE id='.prepare($idgruppo))[0]['nome'];
    $gruppi = [
        'Clienti' => 'Cliente',
        'Tecnici' => 'Tecnico',
        'Agenti' => 'Agente',
    ];
    $nome_gruppo = $gruppi[$nome_gruppo];
}

if (!empty($id_utente)) {
    $op = 'change_pwd';
    $message = tr('Modifica');

    $rs = $dbo->fetchArray('SELECT idanagrafica, username, email FROM zz_users WHERE id='.prepare($id_utente));
    $username = $rs[0]['username'];
    $email = $rs[0]['email'];
	$id_anagrafica = $rs[0]['idanagrafica'];

	// Lettura sedi dell'utente già impostate
	$idsedi = $dbo->fetchOne('SELECT GROUP_CONCAT(idsede) as idsedi FROM zz_user_sedi WHERE id_user='.prepare($id_utente).' GROUP BY id_user')['idsedi'];
	
} else {
    $op = 'adduser';
    $message = tr('Aggiungi');

    $username = '';
    $email = '';
	$id_anagrafica = '';
	
}

$_SESSION['superselect']['idanagrafica'] = $id_anagrafica;

echo '
<form action="" method="post" id="link_form">
	<input type="hidden" name="op" value="'.$op.'">
	<input type="hidden" name="min_length" value="'.$min_length_password.'">
    <input type="hidden" name="min_length_username" value="'.$min_length_username.'">';

if (!empty($id_utente)) {
    echo '
    <input type="hidden" name="id_utente" value="'.$id_utente.'">';
}

if (!$self_edit) {
    echo '
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-12">
		{[ "type": "text", "label": "'.tr('Username').'", "name": "username", "required": 1, "value": "'.$username.'" ]}
		</div>
    </div>


    <div class="row">
		<div class="col-md-12">
		{[ "type": "text", "label": "'.tr('Email').'", "name": "email", "required": 0, "value": "'.$email.'" ]}
		</div>
    </div>';
} else {
    echo '
    <input type="hidden" id="username" name="username" value="'.$username.'">
    <input type="hidden" id="email" name="email" value="'.$email.'">';
}

echo '

	<div class="row">
		<div class="col-md-12">
		{[ "type": "password", "label": "'.tr('Password').'", "name": "password1", "required": 1 ]}
		</div>
    </div>';

echo '
	<div class="row">
		<div class="col-md-12">
		{[ "type": "password", "label": "'.tr('Ripeti la password').'", "name": "password2" ]}
		</div>
	</div>';

if (!$self_edit) {
    echo '

	<div class="row">
		<div class="col-md-12">
		{[ "type": "select", "label": "'.tr('Collega ad una anagrafica').'", "name": "idanag", "required": 1, "ajax-source": "anagrafiche_utenti", "value": "'.$id_anagrafica.'", "icon-after": "add|'.Modules::get('Anagrafiche')['id'].'|tipoanagrafica='.$nome_gruppo.'" ]}
		</div>
	</div>';
	
} else {
    echo '
    <input type="hidden" id="idanag" name="idanag" value="'.$id_anagrafica.'">';
	}

	echo '
	<div class="row">
		<div class="col-md-12">
		{[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede[]",  "ajax-source": "sedi", "multiple":"1", "value":"'.$idsedi.'" ]}
		</div>
	</div>';

	echo '
	<button type="button" onclick="do_submit()" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.$message.'</button>
	<div class="clearfix">&nbsp;</div>
</form>

<script type="text/javascript">
	var min_length = '.$min_length_password.';
	var min_length_username = '.$min_length_username.';
	function do_submit(){
		if( $("#password1").val() == "" || $("#password2").val() == "" )
			swal({
				title: "'.tr('Inserire una password valida.').'",
				type: "error",
			});
		else if( $("#password1").val() != $("#password2").val() )
			swal({
				title: "'.tr('Le password non coincidono.').'",
				type: "error",
			});
		else if( $("#password1").val().length < min_length )
			swal({
				title: "'.tr('La password deve essere lunga minimo _MIN_ caratteri!', [
                    '_MIN_' => $min_length_password,
                ]).'",
				type: "error",
			});
		else if( $("#username").val().length < min_length_username )
			swal({
				title: "'.tr('L\'username deve essere lungo minimo _MIN_ caratteri.', [
                    '_MIN_' => $min_length_username,
                ]).'",
				type: "error",
			});
		else
			$("#link_form").submit();
	}

	$(document).ready(function(){
		$("#idanag").change(function(){
			session_set("superselect,idanagrafica", $(this).val(), 0);
				
			$("#idsede").selectReset();
		})
	});

</script>

<script src="'.$rootdir.'/lib/init.js"></script>';
