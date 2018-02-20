<?php

include_once __DIR__.'/../../core.php';

// Decido la lunghezza minima della password, e la lunghezza minima del nome utente
$min_length = 8;
$min_length_username = 4;

$self_edit = Modules::getPermission('Utenti e permessi') == 'rw' && filter('id_utente') != null;

if ($self_edit) {
    $idgruppo = intval(filter('idgruppo'));
    $id_utente = filter('id_utente');
} else {
    $user = Auth::user();

    $id_utente = $user['id'];
}

if (!empty($id_utente)) {
    $op = 'change_pwd';
    $message = tr('Modifica');

    $rs = $dbo->fetchArray('SELECT idanagrafica, idtipoanagrafica, username FROM zz_users WHERE id='.prepare($id_utente));
    $username = $rs[0]['username'];
    $id_anagrafica = $rs[0]['idtipoanagrafica'].'-'.$rs[0]['idanagrafica'];
} else {
    $op = 'adduser';
    $message = tr('Aggiungi');

    $username = '';
    $id_anagrafica = '';
}

echo '
<form id="link_form" action="'.$rootdir.'/editor.php?id_module='.Modules::get('Utenti e permessi')['id'].'&id_record='.$idgruppo.'" method="post">
	<input type="hidden" name="op" value="'.$op.'">
	<input type="hidden" name="min_length" value="'.$min_length.'">
    <input type="hidden" name="min_length_username" value="'.$min_length_username.'">';

if (!empty($id_utente)) {
    echo '
    <input type="hidden" name="id_utente" value="'.$id_utente.'">';
}

if ($self_edit) {
    echo '
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-xs-12">
		{[ "type": "text", "label": "'.tr('Username').'", "name": "username", "required": 1, "value": "'.$username.'" ]}
		</div>
    </div>';
} else {
    echo '
    <input type="hidden" id="username" name="username" value="'.$username.'">';
}

echo '

	<div class="row">
		<div class="col-xs-12">
		{[ "type": "password", "label": "'.tr('Password').'", "name": "password1", "required": 1, "value": "" ]}
		</div>
    </div>';

echo '
	<div class="row">
		<div class="col-xs-12">
		{[ "type": "password", "label": "'.tr('Ripeti la password').'", "name": "password2", "value": "" ]}
		</div>
	</div>';

if ($self_edit) {
    echo '

	<div class="row">
		<div class="col-xs-12">
		{[ "type": "select", "label": "'.tr('Collega ad una anagrafica').'", "name": "idanag", "values": "query=SELECT CONCAT(`an_tipianagrafiche`.`idtipoanagrafica`, \'-\', `an_anagrafiche`.`idanagrafica`) AS \'id\', `ragione_sociale` AS \'descrizione\', `descrizione` AS \'optgroup\' FROM `an_tipianagrafiche` INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` ORDER BY `descrizione` ASC", "value": "'.$id_anagrafica.'" ]}
		</div>
    </div>';
} else {
    echo '
    <input type="hidden" id="idanag" name="idanag" value="'.$id_anagrafica.'">';
}

echo '

	<button type="button" onclick="do_submit()" class="btn btn-primary"><i class="fa fa-plus"></i> '.$message.'</button>
</form>

<script type="text/javascript">
	var min_length = '.$min_length.';
	var min_length_username = '.$min_length_username.';
	function do_submit(){
		if( $("#password1").val() == "" || $("#password2").val() == "" )
			alert("'.tr('Inserire una password valida').'.");
		else if( $("#password1").val() != $("#password2").val() )
			alert("'.tr('Le password non coincidono').'.");
		else if( $("#password1").val().length < min_length )
			alert("'.tr('La password deve essere lunga minimo _MIN_ caratteri!', [
                '_MIN_' => $min_length,
            ]).'");
		else if( $("#username").val().length < min_length_username )
			alert("'.tr("L'username deve essere lungo minimo _MIN_ caratteri!", [
                '_MIN_' => $min_length_username,
            ]).'");
		else
			$("#link_form").submit();
	}
</script>

<script src="'.$rootdir.'/lib/init.js"></script>';
